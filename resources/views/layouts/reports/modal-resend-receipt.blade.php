<x-modal title="Kirim Receipt" action="{{ $action }}" method="POST" customSubmit="true">
    <div class="col-sm-12">
        <div class="form-group">
            <label>Email <span class="text-danger">*</span></label>
            <input id="email" name="email" type="email" class="form-control" placeholder="email.." required>
        </div>
    </div>

    <div class="modal-footer border-0">
        <button type="submit" id="addRowButton" class="btn btn-primary">Send &nbsp; <i class="fas fa-paper-plane" style="font-size: 18px;"></i></button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
    </div>

</x-modal>
