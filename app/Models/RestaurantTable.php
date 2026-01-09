<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Import QR
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // 👈 Import Str for slugging
class RestaurantTable extends Model
{
    protected $fillable = [
        'restaurant_id',
        'table_number',
        'capacity',
        'status',
        'created_by',

    ];
protected $guarded = [];
    // 👇 Auto-assign Restaurant ID when creating
    protected static function booted()
    {
        
        static::creating(function ($table) {
            if (Auth::check() && !$table->restaurant_id) {
                $table->restaurant_id = Auth::user()->restaurant_id;
            }
            if (Auth::check() && !$table->created_by) {
            $table->created_by = Auth::id();
        }
        });
        static::created(function ($table) {
            
            // 1. Setup URL
            $frontendUrl = env('FRONTEND_URL', 'http://127.0.0.1:3000');
            $scanUrl = "{$frontendUrl}/menu/{$table->restaurant_id}/{$table->id}";

            // 2. Generate QR as SVG (No extensions needed)
            $qrContent = QrCode::format('svg')
                ->size(300)
                ->margin(10)
                ->errorCorrection('H')
                ->generate($scanUrl);
            
            // 3. Prepare the Logo (if exists)
            $logoSvgTag = '';
            $restaurant = $table->restaurant ?? Restaurant::find($table->restaurant_id);
            
            if ($restaurant && $restaurant->logo && file_exists(storage_path('app/public/' . $restaurant->logo))) {
                // Convert logo to Base64 to embed it inside the SVG
                $logoPath = storage_path('app/public/' . $restaurant->logo);
                $logoData = base64_encode(file_get_contents($logoPath));
                $mime = mime_content_type($logoPath);
                
                // Center the logo (Assuming 300x300 QR, logo approx 75x75 in center)
                $logoSvgTag = sprintf(
                    '<image x="112" y="112" width="75" height="75" href="data:%s;base64,%s" />',
                    $mime, $logoData
                );
            }

            // 4. Prepare the Text
            $text = "Table " . $table->table_number;
            // Add a text element at the bottom (y=290)
            $textSvgTag = sprintf(
                '<text x="150" y="290" font-family="Arial" font-size="20" font-weight="bold" text-anchor="middle" fill="black">%s</text>',
                $text
            );

            // 5. Inject Logo & Text into the QR SVG string
            // We strip the closing </svg> tag, add our extras, and close it again.
            $finalSvg = str_replace('</svg>', $logoSvgTag . $textSvgTag . '</svg>', $qrContent);

            // 6. Save File
            $restaurantName = $restaurant ? $restaurant->name : 'default';
            $directory = 'restaurants/' . Str::slug($restaurantName) . '/Tables-QR';
            $fileName = "table_{$table->table_number}.svg"; // Note: .svg extension
            $filePath = "{$directory}/{$fileName}";

            Storage::disk('public')->put($filePath, $finalSvg);
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
