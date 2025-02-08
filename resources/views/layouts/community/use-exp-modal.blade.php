<x-modal title="Exchange Exp" action={{ $action }} method="POST">
    <div class="col-sm-12">
        <div class="form-group">
            <input type="numeric" name="idCommunity" value="{{$idCommunity}}" hidden>
            <label>Exp Digunakan<span class="text-danger">*</span></label>
            <input id="exp_used" name="exp_used" value="{{ $data->exp_used }}" type="number" min="0"
                class="form-control" placeholder="Exp" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Catatan <span class="text-danger">*</span></label>
            <textarea id="catatan" name="catatan" value="{{ $data->catatan }}" type="text" class="form-control"
                placeholder="Catatan" required aria-label="With textarea">{{ $data->catatan }}</textarea>
        </div>
    </div>
</x-modal>

<script>
    var expUseInput = document.getElementById('exp_used');
    var jsonLimit = @json($maxLimitExp);

    expUseInput.addEventListener('keyup', function() {
        // Mengambil nilai dari input
        var value = parseInt(expUseInput.value);

        // Memeriksa apakah nilai lebih dari 100000
        if (this.value > jsonLimit) {
            expUseInput.value = jsonLimit;
        }
    });
</script>
