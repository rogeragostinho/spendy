<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

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

        $validated['paid_by'] = $request->user()->id;

        $expense = Expense::create($validated);

        $expense->users()->attach($request->user()->id, [
            'amount_owed' => $expense->amount
        ]);

        $expense->load('users'); // carrega para pegar os dados atualizados

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

    public function getByGroup(Request $request, int $group_id)
    {
        return response()->json([
            'expenses' => $request->user()
                ->expenses()
                ->where('group_id', $group_id)
                ->get()
        ]);
        return response()->json("entrou");
        return response()->json([
            'expenses' => Expense::where('group_id', $group_id)
                ->whereHas('users', fn($q) => $q->where('id', $request->user()->id))
                ->get()
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

        if ($expense->users->contains($newMember)) {
            return response()->json([
                'message' => 'Já está inserido na despesa'
            ], 403);
        }

        // FIXME: esta parte está errada ou frágil, usar transação
        $expense->users()->attach($validated['user_id'], [
            'amount_owed' => 0
        ]);

        $expense->load('users'); 

        $this->defineAmountOwed($expense);
        //

        return response()->json([
            'message' => 'Membro foi adicionado à despesa'
        ]);
    }

    public function markMemberDebtAsPaid(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $expense = Expense::findOrFail($id);

        $this->permissionForUpdateOrDeleteExpenseVerify($request, $expense);

        $user = User::findOrFail($validated['user_id']);

        $this->verifyGroupMembership($user, $expense->group_id);

        $user = $expense->users->find($validated['user_id']);

        $expense->users()->updateExistingPivot($user->id, [
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
        $amount_owed = (float)($expense->amount / $expense->users->count());

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

        $user = $expense->users()->find($request->user());

        if ($expense->paid_by != $request->user()->id) {
            abort(403, 'Não pode realizar esta operação');
        }
    }

    private function verifyGroupMembership(User $user, int $group_id)
    {
        if (!$user->groups->contains(Group::find($group_id))) {
            abort(403, 'Não pode realizar esta operação');
        }
    }
}
