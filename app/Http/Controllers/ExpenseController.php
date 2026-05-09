<?php

namespace App\Http\Controllers;

use App\Events\ExpenseCreated;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'expenses' => $request->user()->expenses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'amount' => 'required|numeric',
            'description' => 'required|string'
        ]);

        $user = $request->user();

        $this->verifyGroupMembership($user, $validated['group_id']);

        $validated['paid_by'] = $user->id;

        $expense = Expense::create($validated);

        $expense->users()->attach($user->id, [
            'amount_owed' => $expense->amount
        ]);

        $expense->refresh()->load(['users' => function ($query) {
            $query->withPivot('amount_owed', 'is_paid');
        }]);

        ExpenseCreated::dispatch($expense);

        return response()->json([
            'message' => 'Despesa criada com sucesso',
            'expense' => $expense,
            'participants' => $expense->users
        ]);
    }

    public function show(Request $request, int $id)
    {
        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        return response()->json([
            'expense' => $expense,
            'participants' => $expense->users
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'amount' => 'required|decimal:2',
            'description' => 'required'
        ]);

        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        $oldAmount = $expense->amount;
        $expense->update($validated);

        if ((int)$oldAmount != (int)$expense->amount) {
            $this->defineAmountOwed($expense);
        }

        if ($expense->save()) {
            return response()->json([
                'message' => 'Despesa atualizada com sucesso'
            ]);
        }

        return response()->json([
            'message' => 'Ocorreu um erro ao atualizar a despesa'
        ], 404);
    }

    public function destroy(Request $request, int $id)
    {
        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        $expense->delete();

        return response()->json([
            'message' => 'Despesa eliminada'
        ]);
    }

    public function addMember(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        $newMember = User::findOrFail($validated['user_id']);

        $this->verifyGroupMembership($newMember, $expense->group_id);

        if ($expense->users()->where('users.id', $newMember->id)->exists()) {
            return response()->json([
                'message' => 'Já está inserido na despesa'
            ], 403);
        }

        DB::transaction(function () use ($expense, $validated) {
            $expense->users()->attach($validated['user_id'], [
                'amount_owed' => 0
            ]);

            $this->defineAmountOwed($expense);
        });

        $expense->refresh()->load(['users' => function ($q) {
            $q->withPivot('amount_owed', 'is_paid');
        }]);

        return response()->json([
            'message' => 'Membro foi adicionado à despesa',
            'participants' => $expense->users
        ]);
    }

    public function markMemberDebtAsPaid(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        $expense->users()->updateExistingPivot($validated['user_id'], [
            'is_paid' => true
        ]);

        return response()->json([
            'message' => 'Marcado como pago'
        ]);
    }

    // ====================================
    // ======= OTHER FUNCTIONS ===========
    // ====================================

    private function defineAmountOwed(Expense $expense)
    {
        $expense->load('users');

        $count = max($expense->users->count(), 1);

        $amount_owed = (float) ($expense->amount / $count);

        foreach ($expense->users as $user) {
            $expense->users()->updateExistingPivot($user->id, [
                'amount_owed' => $amount_owed
            ]);
        }
    }

    private function permissionForUpdateOrDeleteExpenseVerify(Request $request, Expense $expense)
    {
        if (!$expense->users->contains($request->user())) {
            abort(403, 'Não pode realizar esta operação');
        }

        if ($expense->paid_by != $request->user()->id) {
            abort(403, 'Não pode realizar esta operação');
        }
    }

    private function verifyGroupMembership(User $user, int $group_id)
    {
        if (!$user->groups()->where('groups.id', $group_id)->exists()) {
            abort(403, 'Não pode realizar esta operação');
        }
    }
}
