<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="title">Title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $group->title) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $group->sort_order ?? 0) }}">
    </div>
</div>
