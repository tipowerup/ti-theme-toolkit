@php
    /**
     * $name  — form field name, e.g. "banners"
     * $index — numeric index or "__INDEX__" for the template
     * $row   — array with image, title, description, cta_text, cta_link, preview_url
     */
    $prefix = $name.'['.$index.']';
    $hasImage = !empty($row['image']);
    $previewUrl = $row['preview_url'] ?? '';
@endphp
<div class="banner-manager-row" data-banner-row>
    <div class="image-col">
        <div class="image-slot" title="Click to choose image">
            <img
                data-image-preview
                src="{{ $previewUrl }}"
                alt=""
                style="{{ $hasImage ? '' : 'display:none;' }}"
            >
            <span
                data-image-placeholder
                class="placeholder"
                style="{{ $hasImage ? 'display:none;' : '' }}"
            >
                <i class="fa fa-image d-block mb-1" style="font-size:1.5rem"></i>
                Choose image
            </span>
        </div>
        <input
            type="hidden"
            name="{{ $prefix }}[image]"
            value="{{ $row['image'] }}"
            data-image-input
        >
    </div>

    <div class="fields-col">
        <label>Title</label>
        <input
            type="text"
            class="form-control form-control-sm"
            name="{{ $prefix }}[title]"
            value="{{ $row['title'] }}"
            placeholder="Slide title"
        >

        <label>Description</label>
        <textarea
            class="form-control form-control-sm"
            name="{{ $prefix }}[description]"
            rows="2"
            placeholder="Short description"
        >{{ $row['description'] }}</textarea>

        <div class="cta-row">
            <input
                type="text"
                class="form-control form-control-sm"
                name="{{ $prefix }}[cta_text]"
                value="{{ $row['cta_text'] }}"
                placeholder="CTA button (optional)"
            >
            <input
                type="text"
                class="form-control form-control-sm"
                name="{{ $prefix }}[cta_link]"
                value="{{ $row['cta_link'] }}"
                placeholder="CTA link (optional)"
            >
        </div>
    </div>

    <div class="remove-col">
        <button
            type="button"
            class="btn btn-sm btn-outline-danger banner-manager-remove"
            title="Remove banner"
        >
            <i class="fa fa-times"></i>
        </button>
    </div>
</div>
