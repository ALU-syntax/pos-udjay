<?php

namespace App\Http\Controllers;

use App\DataTables\CategoryDataTable;
use App\Http\Requests\CategoryStoreRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(CategoryDataTable $categoryDataTable){
        return $categoryDataTable->render('layouts.category.index');
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

        return responseSuccess(false);
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

        return responseSuccess(true);
    }

    public function destroy(Category $category){
        $category->delete();

        return responseSuccessDelete();
    }
}
