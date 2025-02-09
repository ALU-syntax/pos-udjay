<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <h5 class="modal-title">
                <span class="fw-mediumbold">
                    Detail Customer</span>

            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Umur</th>
                            <th>Telfon</th>
                            <th>Email</th>
                            <th>Tanggal Lahir</th>
                            <th>Domisili</th>
                            <th>Gender</th>
                            <th>Community</th>
                            <th>Referral</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">{{$data->name}}</th>
                            <td>{{$data->umur}}</murtd>
                            <td>{{$data->telfon}}</td>
                            <td>{{$data->email}}</td>
                            <td>{{$data->tanggal_lahir}}</td>
                            <td>{{$data->domisili}}</td>
                            <td>{{$data->gender}}</td>
                            <td>{{$data->community_id ? $data->community->name : '-'}}</td>
                            <td>{{$data->referral_id ? $data->referral->name : '-'}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
