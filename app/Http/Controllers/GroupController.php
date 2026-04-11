<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class GroupController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'groups' => $request->user()->groups
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required'
        ]);

        $group = new Group();
        $group->name = $validated['name'];
        $group->invite_code = Str::random();

        if ($group->save()) {
            $group->users()->attach($request->user()->id, ['role' => 'admin', 'joined_at' => now()]);

            return response()->json([
                'message' => 'Grupo criado com sucesso'
            ]);
        }

        return response()->json([
            'message' => 'Ocorreu um erro ao criar o grupo'
        ], 404);
    }

    public function show(Request $request, int $id)
    {
        $group = Group::findOrFail($id);

        if (!$group->users->contains($request->user())) {
            return response()->json([
                'message' => 'Não é possível ver essas informações'
            ], 403);
        }

        return response()->json([
            'group' => $group,
            'members' => $group->users
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required'
        ]);

        $group = Group::findOrFail($id);

        $this->permissionForUpdateOrDeleteGroupVerify($request, $group);

        $group->update($validated);

        return response()->json([
            'message' => 'Grupo atualizado'
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $group = Group::findOrFail($id);

        $this->permissionForUpdateOrDeleteGroupVerify($request, $group);

        $group->delete();

        return response()->json([
            'message' => 'Grupo eliminado'
        ]);
    }

    private function permissionForUpdateOrDeleteGroupVerify(Request $request, Group $group)
    {
        if (!$group->users->contains($request->user())) {
            abort(403, 'Não pode realizar esta operação');
        }

        $user = $group->users()->find($request->user());

        if ($user->pivot->role != 'admin') {
            abort(403, 'Não pode realizar esta operação');
        }
    }

    public function join(Request $request, int $id)
    {
        $validated = $request->validate([
            'invite_code' => 'required'
        ]);

        $group = Group::where($validated)->firstOrFail();

        if ($group->users->contains($request->user())) {
            return response()->json([
                'message' => 'Já pertence ao grupo'
            ], 403);
        }

        $group->users()->attach($request->user()->id, ['role' => 'member', 'joined_at' => now()]);

        return response()->json([
            'message' => 'Adicionado ao grupo com sucesso'
        ]);
    }
}
