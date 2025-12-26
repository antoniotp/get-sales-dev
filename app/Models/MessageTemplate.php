<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $chatbot_channel_id Foreign key to chatbot_channels table
 * @property string $name Template name for identification
 * @property string|null $external_template_id ID provided by the messaging platform (WhatsApp, Meta, etc.)
 * @property int $category_id Reference to the message_template_categories table
 * @property string $language Language code (es, en, pt, etc.) - max 10 chars, default 'es'
 * @property string $status Approval status from the messaging platform: pending|approved|rejected|paused|disabled
 * @property int $platform_status Internal status (1=active, 0=inactive)
 * @property string $header_type Type of header content: none|text|image|video|document
 * @property string|null $header_content Header text or media URL
 * @property string $body_content Main message body with variable placeholders like {{1}}, {{2}}
 * @property string|null $footer_content Optional footer text
 * @property array|null $button_config Button configuration as JSON array
 * @property int $variables_count Number of variables in the template ({{1}}, {{2}}, etc.)
 * @property array|null $variables_schema Schema describing each variable (name, type, description) as JSON
 * @property int $usage_count How many times this template has been used
 * @property Carbon|null $last_used_at When this template was last used
 * @property Carbon|null $approved_at When the template was approved by the platform
 * @property string|null $rejected_reason Reason for rejection if status is rejected
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * // Relationships
 * @property ChatbotChannel $chatbotChannel
 * @property MessageTemplateCategory $category
 */
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
