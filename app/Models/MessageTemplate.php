<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chatbot_channel_id',
        'name',
        'external_template_id',
        'category_id',
        'language',
        'status',
        'platform_status',
        'header_type',
        'header_content',
        'body_content',
        'footer_content',
        'button_config',
        'variables_count',
        'variables_schema',
        'usage_count',
        'last_used_at',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'button_config' => 'array',
        'variables_schema' => 'array',
        'variables_count' => 'integer',
        'usage_count' => 'integer',
        'platform_status' => 'integer',
        'last_used_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the chatbot channel that belongs to this template.
     */
    public function chatbotChannel(): BelongsTo
    {
        return $this->belongsTo(ChatbotChannel::class);
    }

    /**
     * Get the category that belongs to this template
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MessageTemplateCategory::class, 'category_id');
    }

    /**
     * Get info about sending
     */
    public function sends(): HasMany
    {
        return $this->hasMany(MessageTemplateSend::class);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('platform_status', 1);
    }

    /**
     * Scope for approved templates
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter by language
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Check if the template is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the template is active
     */
    public function isActive(): bool
    {
        return $this->platform_status === 1;
    }

    /**
     * Increase usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Render template with vars
     */
    public function render(array $variables = []): string
    {
        $content = $this->body_content;

        // replace vars {{1}}, {{2}}, etc.
        foreach ($variables as $index => $value) {
            $placeholder = '{{' . ($index + 1) . '}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Get buttons structure
     */
    public function getButtonsAttribute(): array
    {
        return $this->button_config ?? [];
    }

    /**
     * Get vars schema
     */
    public function getVariablesAttribute(): array
    {
        return $this->variables_schema ?? [];
    }
}
