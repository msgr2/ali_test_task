<?php

use App\Enums\SmsCampaignStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id');
            $table->string('name');
            $table->string('status')->default(SmsCampaignStatusEnum::draft());
            $table->foreignUuid('sms_campaign_plan_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_campaign_sends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sms_campaign_id');
            $table->timestamp('next_step_timestamp')->nullable();
            $table->string('status')->default(SmsCampaignStatusEnum::draft());
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sms_campaign_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('caller');
            $table->string('text');
            $table->json('meta');
            $table->timestamps();
        });
        Schema::create('sms_campaign_autosenders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sms_campaign_id');
            $table->longText('meta');
            $table->timestamps();
        });
        Schema::create('sms_campaign_texts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sms_campaign_id');
            $table->string('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sms_campaign_senderids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sms_campaign_id');
            $table->string('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id');
            $table->string('name');
            $table->string('url');
            $table->decimal('profit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('offer_campaign', function (Blueprint $table) {
            $table->foreignUuid('offer_id')->references('id')->on('offers');
            $table->foreignUuid('sms_campaign_id');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns_structure');
    }
};
