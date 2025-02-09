<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <h5 class="modal-title">
                <span class="fw-mediumbold">
                    Reward Confirmation</span>

            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Checkbox</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $reward)
                            <tr>
                                <td>{{$reward['name']}}</td>
                                <td>
                                    <input type="checkbox" class="form-check-input" id="checkbox1" @if($reward['accept']) checked @endif>
                                </td>
                            </tr>
                        @endforeach
                        <!-- Tambahkan baris lain sesuai kebutuhan -->
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-9">
                    <input type="file">
                </div>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
