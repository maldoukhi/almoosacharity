<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Enums\DocumentType;
use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * "Documents" tab of the beneficiary profile: supporting-document uploads,
 * one media-library collection per DocumentType.
 */
class Documents extends Component
{
    use WithFileUploads;

    public Beneficiary $beneficiary;

    public string $documentType = '';

    public mixed $upload = null;

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * Uploaded media grouped by document-type collection.
     *
     * @return Collection<string, Collection<int, Media>>
     */
    #[Computed]
    public function documents(): Collection
    {
        return collect(DocumentType::cases())
            ->mapWithKeys(fn (DocumentType $type): array => [
                $type->value => $this->beneficiary->getMedia($type->value),
            ]);
    }

    /**
     * @return array<int, DocumentType>
     */
    #[Computed]
    public function documentTypes(): array
    {
        return DocumentType::cases();
    }

    public function updatedUpload(): void
    {
        if ($this->upload !== null) {
            $this->uploadDocument();
        }
    }

    public function uploadDocument(): void
    {
        Gate::authorize('update', $this->beneficiary);

        $validated = $this->validate([
            'documentType' => ['required', 'string', 'in:'.implode(',', array_column(DocumentType::cases(), 'value'))],
            'upload' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->beneficiary
            ->addMedia($validated['upload']->getRealPath())
            ->usingName($validated['upload']->getClientOriginalName())
            ->toMediaCollection($validated['documentType']);

        unset($this->documents);

        $this->reset(['documentType', 'upload']);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.document_uploaded'));
    }

    public function deleteDocument(int $mediaId): void
    {
        Gate::authorize('update', $this->beneficiary);

        $media = $this->beneficiary->media()->whereKey($mediaId)->firstOrFail();

        $media->delete();

        unset($this->documents);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.document_deleted'));
    }

    /**
     * A small base64 data-URI thumbnail for an image document, rendered
     * on the fly via GD. Documents live on a private disk with no public
     * URL, so this deliberately avoids adding a new route or a persisted
     * media-library conversion: the preview is generated in-memory and
     * embedded directly into this authenticated view. Cached briefly
     * per media id + version so repeated renders don't re-decode the
     * original file every time. Returns null for non-image media (or on
     * any decoding failure), so the view falls back to a type icon.
     */
    public function thumbnail(Media $media): ?string
    {
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return null;
        }

        $cacheKey = "beneficiary-document-thumb:{$media->id}:{$media->updated_at?->timestamp}";

        return Cache::remember($cacheKey, now()->addHours(6), fn (): ?string => $this->generateThumbnail($media));
    }

    private function generateThumbnail(Media $media): ?string
    {
        $path = $media->getPath();

        if (! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);

        // Refuse anything implausibly large (or unreadable) to protect
        // memory: this is a best-effort preview, not a guaranteed one.
        if ($info === false || $info[0] <= 0 || $info[1] <= 0 || 4000 * 4000 < $info[0] * $info[1]) {
            return null;
        }

        $raw = @file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        $source = @imagecreatefromstring($raw);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = 160;
        $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($thumb, null, 72);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumb);

        if ($jpeg === false) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.documents');
    }
}
