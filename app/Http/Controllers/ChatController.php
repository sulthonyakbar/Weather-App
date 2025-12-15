<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Chat;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function chat()
    {
        $chats = Chat::orderBy('created_at', 'asc')->get();
        return view('chat', compact('chats'));
    }

    private function saveChat($sender, $message, $type, $caption = null, $attachment_path = null)
    {
        Chat::create([
            'sender' => $sender,
            'message' => $message,
            'type' => $type ?? 'text',
            'caption' => $caption ?? null,
            'attachment_path' => $attachment_path ?? null,
        ]);
    }

    public function handleChat(Request $request)
    {
        $text = strtolower($request->input('message'));
        $this->saveChat('user', $text, 'text', null, null);

        switch (true) {
            case str_contains($text, 'gambarkan cuaca') || str_contains($text, 'buatkan gambar cuaca') || str_contains($text, 'gambar cuaca'):
                $response = $this->gambarCuaca($text);
                $this->saveChat('ai', $response->getData()->url ?? $response->getData()->prompt ?? '', 'image', $response->getData()->caption ?? '', null);
                return $response;

            case str_contains($text, 'video cuaca') || str_contains($text, 'buatkan video cuaca') || str_contains($text, 'tolong video cuaca'):
                $response = $this->videoCuaca($text);
                $this->saveChat('ai', $response->getData()->text ?? '', 'video', $response->getData()->caption ?? '', $response->getData()->attachment_path ?? null);
                return $response;

                // case str_contains($text, 'gif cuaca') || str_contains($text, 'buatkan gif cuaca') || str_contains($text, 'tolong gif cuaca'):
                //     return $this->GIFCuaca($text);

            case str_contains($text, 'audio cuaca') || str_contains($text, 'tts cuaca') || str_contains($text, 'suarakan cuaca'):
                $response = $this->audioCuaca($text);
                $this->saveChat('ai', $response->getData()->text ?? '', 'audio', $response->getData()->caption ?? '', $response->getData()->attachment_path ?? null);
                return $response;

            case str_contains($text, 'cuaca'):
                $response = $this->cuacaBiasa($text);
                $this->saveChat('ai', $response->getData()->reply ?? '', 'text', null, null);
                return $response;

            default:
                break;
        }

        $defaultReply =
            "Halo! 👋 Ada yang bisa saya bantu hari ini?\n\n" .
            "🌦️ Cek cuaca: ketik 'cuaca Malang'\n" .
            "🖼️ Gambar cuaca: 'gambarkan cuaca Malang'\n" .
            "📹 Video cuaca: 'video cuaca Malang'\n";

        $response = $defaultReply;
        $this->saveChat('ai', $response, 'text', null);

        return response()->json([
            "reply" => $response
        ]);
    }

    private function askGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=$apiKey", [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            return "AI error: gagal memproses jawaban.";
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }

    public function cuacaBiasa($text)
    {
        preg_match('/cuaca (.*)/', $text, $match);
        $kota = $match[1] ?? 'Malang';

        $apiKey = env('OPENWEATHER_KEY');

        $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
            'q' => $kota,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'id'
        ]);

        $data = $response->json();

        if (!isset($data['weather']) || !isset($data['main'])) {
            return response()->json([
                "reply" => "Data cuaca tidak ditemukan untuk kota {$kota}."
            ]);
        }

        $cuaca = $data['weather'][0]['description'] ?? '';
        $suhu = $data['main']['temp'] ?? '';
        $terasa = $data['main']['feels_like'] ?? '';
        $kelembapan = $data['main']['humidity'] ?? '';
        $angin = $data['wind']['speed'] ?? '';

        $promptText = "
            Buatkan respon ramah, natural, dan seperti asisten cuaca.
            Jelaskan kepada pengguna tentang kondisi cuaca saat ini di kota {$kota}.

            Berikut data cuacanya:
            - Kondisi: {$cuaca}
            - Suhu: {$suhu}°C
            - Terasa seperti: {$terasa}°C
            - Kelembapan: {$kelembapan}%
            - Kecepatan angin: {$angin} m/s

            Berikan satu paragraf saja, jangan terlalu panjang, jangan tampilkan bullet list.
            Buat seperti percakapan WhatsApp.
        ";

        $textResponse = $this->askGemini($promptText);

        return response()->json([
            "reply" => $textResponse,
            "weather" => $data['weather'][0]['main'] ?? null
        ]);
    }

    public function cekCuaca($kota)
    {
        $apiKey = env('OPENWEATHER_KEY');

        $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
            'q' => $kota,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'id'
        ]);

        if (!$response->successful()) {
            return [
                "deskripsi" => "tidak diketahui",
                "suhu" => "--"
            ];
        }

        $data = $response->json();

        return [
            "deskripsi" => $data["weather"][0]["description"] ?? "tidak diketahui",
            "suhu" => $data["main"]["temp"] ?? "--",
        ];
    }

    public function gambarCuaca($text)
    {
        preg_match('/(gambarkan|gambar|buatkan)( cuaca)? (.*)/', $text, $match);
        $kota = $match[3] ?? 'Malang';

        $cuaca = $this->cekCuaca($kota);

        $promptGambar = "
            Bukatkan kalimat dengan gabungan elemen berikut menjadi deskripsi visual singkat (maksimal 5 kata):
            - Cuaca: {$cuaca['deskripsi']}
            - Suhu: {$cuaca['suhu']}°C
            - Kota: {$kota}
            Contoh: 'hujan deras di kota Jakarta'
            Berikan hanya frasa visual, tanpa kalimat tambahan.
        ";

        $gambarResponse = $this->askGemini($promptGambar);

        $url = "https://image.pollinations.ai/prompt/" . urlencode($gambarResponse);

        return response()->json([
            "type" => "image",
            "kota" => $kota,
            "prompt" => $gambarResponse,
            "url" => $url,
            "caption" => "Berikut gambar cuaca untuk kota {$kota}."
        ]);
    }

    public function videoCuaca($text)
    {
        preg_match('/(video cuaca|buatkan video cuaca|tolong video cuaca) (.*)/', $text, $match);
        $kota = $match[2] ?? 'Malang';

        $cuaca = $this->cekCuaca($kota);
        $promptVideo = "
            Buatkan naskah singkat seperti presenter berita cuaca sedang membacakan laporan langsung.
            Naskah harus terdengar natural, ramah, dan profesional. Data cuaca:
            - Kota: $kota
            - Kondisi: {$cuaca['deskripsi']}
            - Suhu: {$cuaca['suhu']} derajat Celcius
            Buat naskah maksimal 2 kalimat, jelas, komunikatif, dan cocok untuk dibacakan di video cuaca.
            Jangan menambahkan data lain di luar informasi di atas.
        ";

        $videoResponse = $this->askGemini($promptVideo);

        // Generate video HeyGen
        $response = Http::withToken(env('HEYGEN_KEY'))
            ->post("https://api.heygen.com/v2/video/generate", [
                "video_inputs" => [[
                    "character" => [
                        "type" => "talking_photo",
                        "talking_photo_id" => env('HEYGEN_CHARACTER_ID')
                    ],
                    "voice" => [
                        "type" => "text",
                        "input_text" => $videoResponse,
                        "voice_id" => env('HEYGEN_VOICE_ID'),
                        "speed" => "1.0"
                    ]
                ]],
                "dimension" => [
                    "width" => 720,
                    "height" => 1280
                ]
            ]);

        $videoId = $response["data"]["video_id"];

        // menunggu video selesai
        while (true) {
            sleep(5);

            $status = Http::withToken(env('HEYGEN_KEY'))
                ->get("https://api.heygen.com/v1/video_status.get", [
                    "video_id" => $videoId
                ])
                ->json();

            if (($status["data"]["status"] ?? '') === "completed") {

                $remoteUrl = $status["data"]["video_url"];

                $saved = $this->saveVideo($remoteUrl);

                return response()->json([
                    "type" => "video",
                    "status" => "completed",
                    "text" => $videoResponse,
                    "video_url" => $remoteUrl,
                    "attachment_path" => $saved['path'] ?? null,
                    "caption" => "Berikut video cuaca untuk kota {$kota}."
                ]);
            }
        }
    }

    public function saveVideo($videoUrl)
    {
        try {
            $filename = 'weather_' . time() . '.mp4';

            // Pastikan folder exists
            Storage::disk('public')->makeDirectory('videos');

            // Download menggunakan HTTP client Laravel (lebih aman dari file_get_contents)
            $response = Http::timeout(120)->get($videoUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Gagal mengunduh video dari URL.'
                ];
            }

            // Simpan video ke storage/app/public/videos/
            Storage::disk('public')->put('videos/' . $filename, $response->body());

            return [
                'success' => true,
                'filename' => $filename,
                'path' => asset('storage/videos/' . $filename)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // public function GIFCuaca($text)
    // {
    //     preg_match('/(gif cuaca|buatkan gif cuaca|tolong gif cuaca) (.*)/', $text, $match);
    //     $kota = $match[2] ?? 'Malang';

    //     $cuaca = $this->cekCuaca($kota);

    //     $promptGIF = "
    //         Buatkan keyword maksimal 2 kata untuk GIF tentang kondisi cuaca berikut:
    //         - Deskripsi cuaca: {$cuaca['deskripsi']}
    //         - Suhu: {$cuaca['suhu']} derajat celcius.'
    //         Contoh: 'hujan deras', 'cerah berawan'
    //         Berikan hanya keyword, tanpa kalimat tambahan.
    //     ";

    //     $gifResponse = $this->askGemini($promptGIF);

    //     $url = "https://g.tenor.com/v1/search?q="
    //         . urlencode($gifResponse)
    //         . "&key=LIVDSRZULELA&limit=1";

    //     $response = Http::get($url)->json();

    //     $gifUrl = $response['results'][0]['media'][0]['gif']['url']
    //         ?? $response['results'][0]['media'][0]['tinygif']['url']
    //         ?? null;

    //     return response()->json([
    //         "type" => "gif",
    //         "keyword" => $gifResponse,
    //         "url" => $gifUrl,
    //         "caption" => "Berikut GIF cuaca untuk kota {$kota}."
    //     ]);
    // }

    public function audioCuaca($text)
    {
        preg_match('/(suarakan cuaca|audio cuaca|tts cuaca) (.*)/', $text, $match);
        $kota = $match[2] ?? 'Malang';

        $cuaca = $this->cekCuaca($kota);

        $promptAudio = "
            Buatkan narasi audio dengan gaya ramah dan informatif tentang kondisi cuaca berikut:
            - Kota: $kota
            - Deskripsi cuaca: {$cuaca['deskripsi']}
            - Suhu: {$cuaca['suhu']} derajat celcius.
            Hasilkan narasi deskriptif singkat, jelas, dan mudah dipahami (MAKSIMAL 200 KARAKTER). Jangan tambahkan informasi di luar data yang diberikan.
            Contoh: 'Halo, ini laporan cuaca untuk kota Malang. Saat ini kondisi adalah cerah dengan suhu 30 derajat Celcius. Semoga harimu menyenangkan!'
        ";

        $audioResponse = $this->askGemini($promptAudio);

        $savedAudio = $this->saveAudio($audioResponse);

        if (!$savedAudio['success']) {
            return [
                'type' => 'text',
                'reply' => 'Gagal membuat audio cuaca.'
            ];
        }

        return response()->json([
            "type" => "audio",
            "text" => $audioResponse,
            "attachment_path" => $savedAudio['path'] ?? null,
            "caption" => "Berikut audio cuaca untuk kota {$kota}."
        ]);
    }

    public function saveAudio($audioResponse)
    {
        try {
            $filename = 'audio_weather_' . time() . '.mp3';

            Storage::disk('public')->makeDirectory('audio');

            $ttsUrl = "https://translate.google.com/translate_tts?ie=UTF-8&q="
                . urlencode($audioResponse)
                . "&tl=id&client=tw-ob&ttsspeed=1";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0'
            ])->get($ttsUrl);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Gagal generate audio'];
            }

            Storage::disk('public')->put('audio/' . $filename, $response->body());

            return [
                'success' => true,
                'path' => asset('storage/audio/' . $filename),
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function clearChat()
    {
        Chat::truncate();
        return redirect()->route('chat');
    }
}
