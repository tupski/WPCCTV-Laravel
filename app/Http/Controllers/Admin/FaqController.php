<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::with('context')->orderBy('sort_order')->latest()->paginate(25);

        return response()->json(['data' => $faqs, 'meta' => $faqs->toArray()['meta'] ?? null]);
    }

    public function store(StoreFaqRequest $request)
    {
        $faq = Faq::create($request->safe()->all());

        return response()->json(['data' => $faq], 201);
    }

    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq->update($request->safe()->all());

        return response()->json(['data' => $faq->fresh()]);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ berhasil dihapus.']);
    }
}
