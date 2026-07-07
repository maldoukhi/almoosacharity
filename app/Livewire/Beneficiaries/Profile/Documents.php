<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Enums\DocumentType;
use App\Models\Beneficiary;
use Illuminate\Support\Collection;
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

    public function render()
    {
        return view('livewire.beneficiaries.profile.documents');
    }
}
