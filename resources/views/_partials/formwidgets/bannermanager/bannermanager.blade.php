@php
    /**
     * $name     — html form name for the field, e.g. "banners"
     * $value    — array of rows: [['image' => 'path', 'title' => '', ...], ...]
     * $alias    — widget alias (unique per widget instance on a page)
     * $emptyRow — blank-row shape for the "Add banner" template
     */
    $wrapperId = 'banner-manager-'.$alias;
@endphp
<div id="{{ $wrapperId }}" class="banner-manager" data-banner-manager data-field-name="{{ $name }}">
    <div class="banner-manager-rows">
        @foreach($value as $i => $row)
            @include('tipowerup.theme-toolkit::_partials.formwidgets.bannermanager.row', [
                'name' => $name,
                'index' => $i,
                'row' => $row,
            ])
        @endforeach
    </div>

    <button type="button" class="btn btn-light btn-sm banner-manager-add mt-2">
        <i class="fa fa-plus"></i> Add banner
    </button>

    {{-- Template for a new empty row — cloned by JS on "Add banner" click --}}
    <template data-banner-manager-template>
        @include('tipowerup.theme-toolkit::_partials.formwidgets.bannermanager.row', [
            'name' => $name,
            'index' => '__INDEX__',
            'row' => array_merge($emptyRow, ['preview_url' => '']),
        ])
    </template>
</div>

@once
<style>
    .banner-manager-row {
        display: grid;
        grid-template-columns: 140px 1fr auto;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        margin-bottom: 0.75rem;
        background: #fafafa;
    }
    .banner-manager-row .image-slot {
        position: relative;
        width: 140px;
        height: 90px;
        background: #fff;
        border: 1px dashed #d1d5db;
        border-radius: 0.25rem;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .banner-manager-row .image-slot img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    .banner-manager-row .image-slot .placeholder {
        color: #9ca3af;
        font-size: 0.75rem;
        text-align: center;
    }
    .banner-manager-row .fields-col .form-control,
    .banner-manager-row .fields-col textarea.form-control {
        margin-bottom: 0.5rem;
    }
    .banner-manager-row .fields-col label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.1rem;
    }
    .banner-manager-row .cta-row {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 0.5rem;
    }
    .banner-manager-row .remove-col {
        display: flex;
        align-items: flex-start;
    }
</style>
@endonce

<script>
(function () {
    var wrapper = document.getElementById(@json($wrapperId));
    if (!wrapper || wrapper.dataset.initialized === '1') return;
    wrapper.dataset.initialized = '1';

    var rowsEl = wrapper.querySelector('.banner-manager-rows');
    var template = wrapper.querySelector('[data-banner-manager-template]');

    function nextIndex() {
        return rowsEl.querySelectorAll('.banner-manager-row').length;
    }

    wrapper.querySelector('.banner-manager-add').addEventListener('click', function () {
        rowsEl.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, nextIndex()));
    });

    rowsEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.banner-manager-remove');
        if (btn) {
            btn.closest('.banner-manager-row').remove();
            return;
        }

        var slot = e.target.closest('.image-slot');
        if (!slot) return;
        e.preventDefault();

        if (typeof $ === 'undefined' || !$.ti || !$.ti.mediaManager) {
            if ($.ti && $.ti.flashMessage) {
                $.ti.flashMessage({ class: 'danger', text: 'Media manager is not available.' });
            }
            return;
        }

        var row = slot.closest('.banner-manager-row');
        var hiddenInput = row.querySelector('[data-image-input]');
        var preview = row.querySelector('[data-image-preview]');
        var placeholder = row.querySelector('[data-image-placeholder]');

        new $.ti.mediaManager.modal({
            alias: 'mediamanager',
            selectMode: 'single',
            chooseButton: true,
            chooseButtonText: 'Use this image',
            goToItem: hiddenInput.value || null,
            onInsert: function (items) {
                if (!items.length) return;
                var data = $(items).find('[data-media-item-path]').data('mediaItemData');
                if (!data) return;

                hiddenInput.value = data.path;
                if (preview) {
                    preview.src = data.publicUrl;
                    preview.style.display = '';
                }
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                this.hide();
            },
        });
    });
})();
</script>
