<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comic extends Model
{
    protected $fillable = [
        'title',
        'description',
        'author',
        'genre',
        'category_id',
        'price',
        'status',
        'featured',
        'image_url',
        'user_id'
    ];

    protected $casts = [
        'featured' => 'boolean',
        'price' => 'decimal:2',
        'category_id' => 'integer'
    ];

    protected $attributes = [
        'featured' => false,
        'status' => 'published',
        'price' => '0.00'
    ];

   public function setPriceAttribute($value)
   {
       $this->attributes['price'] = number_format((float)$value, 2, '.', '');
   }

   public function getPriceAttribute($value)
   {
       return number_format((float)$value, 2, '.', '');
   }

   public function user()
   {
       return $this->belongsTo(User::class);
   }
   
   public function category()
   {
       return $this->belongsTo(Category::class);
   }
}