<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\AiKnowlegdeArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AiKnowledgeBase
{
    public function search(string $query, int $limit = 5): Collection
    {
        $normalized = $this->normalize($query);
        $tokens = $this->tokens($normalized);

        $articles = AiKnowlegdeArticle::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('updated_at')
            ->get();

        $ranked = $articles->map(function (AiKnowlegdeArticle $article) use ($normalized, $tokens): array {
            $haystack = $this->normalize(implode(' ', array_filter([
                $article->title,
                $article->question,
                $article->answer,
                is_array($article->tags) ? implode(' ', $article->tags) : null,
                $article->category,
            ])));

            $score = 0.1;
            foreach ($tokens as $token) {
                if ($token !== '' && Str::contains($haystack, $token)) {
                    $score += 0.2;
                }
            }

            if ($normalized !== '' && Str::contains($haystack, $normalized)) {
                $score += 0.35;
            }

            return [
                'article' => $article,
                'score' => min(0.99, $score),
            ];
        })->sortByDesc('score')->take($limit)->values();

        return $ranked->pluck('article');
    }

    public function answer(string $query, string $channelSlug, ?User $user = null): array
    {
        $articles = $this->search($query);

        if ($articles->isNotEmpty()) {
            $best = $articles->first();

            return [
                'found' => true,
                'title' => $best->title,
                'answer' => $best->answer,
                'articles' => $articles->map(fn (AiKnowlegdeArticle $article): array => [
                    'title' => $article->title,
                    'category' => $article->category,
                    'priority' => $article->priority,
                    'tags' => $article->tags ?? [],
                ])->all(),
                'channel' => $channelSlug,
            ];
        }

        return [
            'found' => false,
            'title' => 'Tidak ada artikel yang cocok',
            'answer' => $this->fallbackAnswer($query, $channelSlug, $user),
            'articles' => [],
            'channel' => $channelSlug,
        ];
    }

    public function seedFaq(): array
    {
        return [
            [
                'title' => 'Cara scan barcode transaksi',
                'question' => 'Bagaimana cara scan barcode barang saat transaksi?',
                'answer' => 'Buka halaman transaksi, fokuskan kamera atau scanner ke barcode barang, lalu data produk akan masuk otomatis ke tabel transaksi. Ulangi hingga semua barang selesai, kemudian klik hitung dan simpan.',
                'category' => 'transactions',
                'tags' => ['scan', 'barcode', 'transaksi'],
                'priority' => 10,
            ],
            [
                'title' => 'Cara cek selisih stok',
                'question' => 'Bagaimana membandingkan stok sistem dan stok fisik?',
                'answer' => 'Gunakan menu stock adjustment untuk mencatat stok sistem dan stok fisik, lalu core akan menghitung selisih dan memberi status matched, system_correct, atau system_updated.',
                'category' => 'stock-adjustments',
                'tags' => ['stok', 'selisih', 'fisik', 'sistem'],
                'priority' => 10,
            ],
            [
                'title' => 'Cara proses supplier return',
                'question' => 'Bagaimana alur return ke supplier?',
                'answer' => 'Pilih supplier, location, product, lalu batch yang akan dikembalikan. Sistem akan mengurangi qty_remaining, mengarsipkan batch terkait, dan menulis jejak ke stock movements.',
                'category' => 'supplier-returns',
                'tags' => ['return', 'supplier', 'batch'],
                'priority' => 10,
            ],
            [
                'title' => 'Kenapa login gagal',
                'question' => 'Mengapa login gagal padahal password benar?',
                'answer' => 'Periksa username, status aktif akun, dan status role. Jika salah satu nonaktif, sistem akan menolak login dan menampilkan alasan yang valid.',
                'category' => 'auth',
                'tags' => ['login', 'password', 'role'],
                'priority' => 10,
            ],
        ];
    }

    protected function fallbackAnswer(string $query, string $channelSlug, ?User $user = null): string
    {
        $role = strtolower((string) data_get($user, 'role.name', 'guest'));

        return match ($channelSlug) {
            'manager-chatbot' => 'Saya belum menemukan artikel yang tepat. Coba minta ringkasan harian, stok, return, atau status produk.',
            'warehouse-search' => 'Saya belum menemukan data spesifik. Coba gunakan kata kunci nama produk, SKU, barcode, batch, return, atau selisih stok.',
            'customer-service' => 'Saya belum menemukan jawaban spesifik. Coba jelaskan error, menu, atau langkah yang sedang Anda lakukan.',
            default => 'Saya belum menemukan rujukan yang cocok. Anda bisa mempersempit pertanyaan atau arahkan ke modul yang spesifik.',
        };
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    protected function tokens(string $value): array
    {
        return collect(explode(' ', $value))
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
