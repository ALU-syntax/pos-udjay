<?php

namespace App\Http\Controllers;

use App\DataTables\CategoryDataTable;
use App\Http\Requests\CategoryStoreRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(CategoryDataTable $categoryDataTable){
        $stats = [
            'total' => Category::count(),
            'active' => Category::where('status', 1)->count(),
            'inactive' => Category::where('status', 0)->count(),
            'reward' => Category::where('reward_categories', 1)->count(),
        ];

        return $categoryDataTable->render('layouts.category.index', compact('stats'));
    }

    public function create(){
        return view('layouts.category.category-modal', [
            'data' => new Category(),
            'action' => route('library/category/store')
        ]);
    }

    public function store(CategoryStoreRequest $request){
        $category = new Category($request->validated());
        $category->save();

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('status', 1)->count(),
            'inactive' => Category::where('status', 0)->count(),
            'reward' => Category::where('reward_categories', 1)->count(),
        ];


        return responseSuccess(false, false, $stats);
    }

    public function edit(Category $category){
        return view('layouts.category.category-modal',[
            'data' => $category,
            'action' => route('library/category/update', $category->id)
        ]);
    }

    public function update(CategoryStoreRequest $request, Category $category){
        $category->fill($request->validated());
        $category->save();

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('status', 1)->count(),
            'inactive' => Category::where('status', 0)->count(),
            'reward' => Category::where('reward_categories', 1)->count(),
        ];

        return responseSuccess(true, false, $stats);
    }

    public function destroy(Category $category){
        $category->delete();

        return responseSuccessDelete();
    }

    public function getAllCategory(){
        return response()->json([
            'data' => Category::all()
        ]);
    }
}
