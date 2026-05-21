<?php
// app/Http/Requests/Admin/UpdateArticleRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        $articleId = $this->route('article')->id ?? $this->route('id');

        return [
            'title' => 'required|string|max:255|unique:articles,title,' . $articleId,
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:article_categories,id',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'in:draft,published',
            'published_at' => 'nullable|date_format:Y-m-d H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul artikel harus diisi',
            'title.unique' => 'Judul artikel sudah ada',
            'excerpt.required' => 'Ringkasan artikel harus diisi',
            'content.required' => 'Konten artikel harus diisi',
            'category_id.required' => 'Kategori harus dipilih',
            'featured_image.image' => 'File harus berupa gambar',
            'featured_image.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }
}