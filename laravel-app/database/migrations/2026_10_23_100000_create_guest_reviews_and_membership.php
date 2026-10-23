<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGuestReviewsAndMembership extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('guest_reviews')) {
            Schema::create('guest_reviews', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 80)->nullable();
                $table->string('guest_name', 80)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('phone', 32)->nullable();
                $table->timestamp('phone_verified_at')->nullable();
                $table->unsignedTinyInteger('cleanliness');
                $table->unsignedTinyInteger('on_time');
                $table->unsignedTinyInteger('service');
                $table->unsignedTinyInteger('food_quality');
                $table->text('comment')->nullable();
                $table->longText('signature')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('customer_groups')) {
            if (! Schema::hasColumn('customer_groups', 'discount_mode')) {
                Schema::table('customer_groups', function (Blueprint $table) {
                    $table->string('discount_mode', 20)->default('discount');
                });
            }
            if (! Schema::hasColumn('customer_groups', 'is_system')) {
                Schema::table('customer_groups', function (Blueprint $table) {
                    $table->boolean('is_system')->default(0);
                });
            }
        }

        if (! Schema::hasTable('membership_plans')) {
            Schema::create('membership_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->unsignedSmallInteger('duration_months');
                $table->decimal('fee', 14, 2)->default(0);
                $table->boolean('is_active')->default(1);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_promotions')) {
            Schema::create('membership_promotions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('type', 40)->default('free_days');
                $table->unsignedInteger('free_days')->default(90);
                $table->boolean('is_enabled')->default(1);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_agreements')) {
            Schema::create('membership_agreements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('version', 40);
                $table->string('title');
                $table->longText('body');
                $table->boolean('is_current')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_settings')) {
            Schema::create('membership_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('key', 80)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_applications')) {
            Schema::create('membership_applications', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 40)->unique();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedInteger('agreement_id')->nullable();
                $table->unsignedInteger('promotion_id')->nullable();
                $table->unsignedInteger('customer_id')->nullable();
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone');
                $table->string('id_type', 40)->nullable();
                $table->string('id_number', 64)->nullable();
                $table->date('id_expires_on')->nullable();
                $table->string('nationality', 80)->nullable();
                $table->string('id_photo_path')->nullable();
                $table->string('selfie_path')->nullable();
                $table->string('status', 40)->default('PENDING');
                $table->decimal('fee', 14, 2)->default(0);
                $table->text('admin_note')->nullable();
                $table->longText('signature_image')->nullable();
                $table->dateTime('signed_at')->nullable();
                $table->string('submitted_ip', 45)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->increments('id');
                $table->string('number', 40)->unique();
                $table->unsignedInteger('customer_id')->index();
                $table->unsignedInteger('application_id')->nullable();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedInteger('promotion_id')->nullable();
                $table->string('status', 40)->default('ACTIVE');
                $table->boolean('is_promotional')->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->string('qr_token', 64)->unique();
                $table->timestamps();
            });
        }

        $this->seed();
    }

    private function seed()
    {
        $now = now();
        $groupId = null;
        if (Schema::hasTable('customer_groups') && ! DB::table('customer_groups')->where('name', 'Skyline Hotel Members')->exists()) {
            $row = [
                'name' => 'Skyline Hotel Members',
                'percentage' => '10',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('customer_groups', 'discount_mode')) {
                $row['discount_mode'] = 'discount';
            }
            if (Schema::hasColumn('customer_groups', 'is_system')) {
                $row['is_system'] = 1;
            }
            $groupId = DB::table('customer_groups')->insertGetId($row);
        } elseif (Schema::hasTable('customer_groups')) {
            $groupId = optional(DB::table('customer_groups')->where('name', 'Skyline Hotel Members')->first())->id;
        }

        $plans = [
            ['monthly', 'Monthly', 1, 15000, 1],
            ['quarterly', 'Quarterly', 3, 40000, 2],
            ['six_months', 'Six Months', 6, 75000, 3],
            ['annual', 'Annual', 12, 140000, 4],
        ];
        foreach ($plans as $plan) {
            if (! DB::table('membership_plans')->where('code', $plan[0])->exists()) {
                DB::table('membership_plans')->insert([
                    'code' => $plan[0],
                    'name' => $plan[1],
                    'duration_months' => $plan[2],
                    'fee' => $plan[3],
                    'is_active' => 1,
                    'sort_order' => $plan[4],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! DB::table('membership_promotions')->count()) {
            DB::table('membership_promotions')->insert([
                'name' => 'FREE membership for 90 days',
                'type' => 'free_days',
                'free_days' => 90,
                'is_enabled' => 1,
                'starts_at' => $now,
                'ends_at' => null,
                'description' => 'Approved applicants pay nothing and receive 90 days of membership.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('membership_agreements')->where('is_current', 1)->exists()) {
            DB::table('membership_agreements')->insert([
                'version' => '1.0',
                'title' => 'Skyline Hotel Membership Agreement',
                'body' => "Skyline Hotel Membership Terms\n\n"
                    ."1. Membership is personal and cannot be transferred.\n"
                    ."2. Active members receive the published member discount while membership is active.\n"
                    ."3. Fees are set by the hotel and may change. You pay the fee in force when you join or renew.\n"
                    ."4. A free promotional period ends on the stated date unless you renew on a paid plan.\n"
                    ."5. When membership expires or is suspended, the member discount stops.\n"
                    ."6. The hotel may refuse or suspend membership for misuse.\n"
                    ."7. The hotel may contact you on WhatsApp about membership and renewals.\n"
                    ."8. Identification you upload is used only to verify membership.\n",
                'is_current' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($groupId && ! DB::table('membership_settings')->where('key', 'member_group_id')->exists()) {
            DB::table('membership_settings')->insert([
                'key' => 'member_group_id',
                'value' => (string) $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_applications');
        Schema::dropIfExists('membership_settings');
        Schema::dropIfExists('membership_agreements');
        Schema::dropIfExists('membership_promotions');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('guest_reviews');
    }
}
