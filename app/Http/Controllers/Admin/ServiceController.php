<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(25);

        return response()->json(['data' => $services, 'meta' => $services->toArray()['meta'] ?? null]);
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->safe()->all());

        return response()->json(['data' => $service], 201);
    }

    public function show(Service $service)
    {
        return response()->json(['data' => $service->load('seo')]);
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->safe()->all());

        return response()->json(['data' => $service->fresh()]);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(['message' => 'Layanan berhasil dihapus.']);
    }
}
