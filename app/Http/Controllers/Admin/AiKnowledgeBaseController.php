<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKnowledgeBase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiKnowledgeBaseController extends Controller
{
    public function index()
    {
        $knowledges = AiKnowledgeBase::latest()->get();
        $trainingPairs = \App\Models\AiTrainingPair::latest()->limit(100)->get(); // Ambil max 100 log terbaru agar tidak berat

        return Inertia::render('Admin/AiKnowledgeBase', [
            'knowledges' => $knowledges,
            'trainingPairs' => $trainingPairs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'keywords' => 'nullable|string',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        AiKnowledgeBase::create($validated);
        return redirect()->back()->with('success', 'Knowledge Base berhasil ditambahkan.');
    }

    public function update(Request $request, AiKnowledgeBase $knowledge)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'keywords' => 'nullable|string',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $knowledge->update($validated);
        return redirect()->back()->with('success', 'Knowledge Base berhasil diupdate.');
    }

    public function destroy(AiKnowledgeBase $knowledge)
    {
        $knowledge->delete();
        return redirect()->back()->with('success', 'Knowledge Base berhasil dihapus.');
    }
}
