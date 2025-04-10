<x-modal title="Kirim Receipt" action="{{ $action }}"
    method="POST">
    <div class="col-sm-12">
        <div class="form-group">
            <label>Email <span class="text-danger">*</span></label>
            <input id="email" email="name" type="text" class="form-control"
                placeholder="email.." required>
        </div>
    </div>
</x-modal>
