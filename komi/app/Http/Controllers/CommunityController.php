<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CommunityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $counter = 1;
        $communities = Community::where('is_active', 1)->get();
        return view('communities.index', compact('communities', 'counter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('is_active', 1)->get();
        return view('communities.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            ''
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $community = Community::where('id', Crypt::decrypt($id))->where('is_active', 1)->firstOrFail();
        return view('communities.show', compact('community'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $users = User::where('is_active', 1)->get();
        $community = Community::where('id', Crypt::decrypt($id))->where('is_active', 1)->firstOrFail();
        return view('communities.edit', compact('community', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Community $community)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Community $community)
    {
        //
    }
}