    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->nullable()->constrained();
                $table->string('name');
                $table->string('slug')->unique()->nullable();
                $table->string('uom')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->unsignedBigInteger('qty')->default(0);
                $table->unsignedBigInteger('reorderlevel')->default(0);
                $table->unsignedBigInteger('qtytoorder')->default(0);
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('items');
        }
    };
