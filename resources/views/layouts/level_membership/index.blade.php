@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Level Membership</h5>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Setup Item Birthday Reward</h5>
                        </div>
                        <div class="card-body">
                            <div class="row pe-2">
                                <div class="col-12">
                                    <select class="dropdown-custom w-100" name="birthday_reward" id="birthday_reward">
                                        @if ($birthday_reward_choose)
                                            <option selected value="{{ $birthday_reward_choose->product->category_id }}">{{ $birthday_reward_choose->product_name }} - {{ $birthday_reward_choose->product->category->name }}</option>
                                        @else
                                            <option disabled selected>Belum ada item terpilih</option>
                                        @endif

                                        @foreach ($product_birthday_rewards as $productReward)
                                            <option value="{{ $productReward->category_id }}">{{ $productReward->name }} - {{ $productReward->category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mt-2">
                                    <textarea id="birthday-reward-desc" name="birthday-reward-desc" rows="4" class="form-control w-100"
                                        placeholder="Description reward, Contoh: Gratis 1 menu 'Signature Drink' pada bulan ulang tahun.">{{ $birthday_reward_choose->product->description ?? '' }}</textarea>
                                </div>
                                <div class="col-2 mt-2">
                                    <button class="btn btn-primary" id="btn-simpan-birthday-reward">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Setup Item Reward 5000 Exp</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <select class="dropdown-custom w-100" name="exp_reward" id="exp_reward">
                                        @if ($exp_reward_choose)
                                            <option selected value="{{ $exp_reward_choose->product->category_id }}">
                                                {{ $exp_reward_choose->product_name }} - {{ $exp_reward_choose->product->category->name }}</option></option>
                                        @else
                                            <option disabled selected>Belum ada item terpilih</option>
                                        @endif

                                        @foreach ($product_exp_rewards as $productReward)
                                            <option value="{{ $productReward->category_id }}">{{ $productReward->name }} - {{ $productReward->category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mt-2">
                                    <textarea id="exp-reward-desc" name="exp-reward-desc" rows="4" class="form-control w-100"
                                        placeholder="Description reward, Contoh: Tukar setiap 5.000 EXP menjadi 1 item gratis.">{{ $exp_reward_choose->product->description ?? '' }}</textarea>
                                </div>
                                <div class="col-2 mt-2">
                                    <button class="btn btn-primary" id="btn-simpan-exp-reward">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-1">
            <div class="card-header d-flex justify-content-end">

                @can('create membership/level-membership')
                    <a href="{{ route('membership/level-membership/create') }}" type="button"
                        class="btn btn-lg btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i> Tambah Level
                        Membership</a>
                @endcan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
                </div>
            </div>
        </div>
    </div>


    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            var success = "{{ session('success') }}";
            const datatable = 'levelmembership-table';
            var birthdayRewardExist = "{{ $birthday_reward_choose->product_name ?? '' }}";
            var descriptionRewardExist = "{{ $birthday_reward_choose->product->description ?? '' }}";

            var expRewardExist = "{{ $exp_reward_choose->product_name ?? '' }}";
            var descriptionExpRewardExist = "{{ $exp_reward_choose->product->description ?? '' }}";

            handleAction(datatable);
            handleDelete(datatable);

            $('#btn-simpan-birthday-reward').off().on('click', function() {
                let categoryBirthdayReward = $('#birthday_reward').val();
                let selectedBirthdayReward = $('#birthday_reward option:selected').text();
                let splitNameProductCategory = selectedBirthdayReward.split('-');
                let nameBirthdayReward = splitNameProductCategory[0].trim();
                let descriptionValue = $('#birthday-reward-desc').val();

                if(!categoryBirthdayReward){
                    Swal.fire(
                        'Ooops!',
                        'Item tidak boleh kosong.',
                        'warning'
                    );
                    return;
                }

                // validasi jika reward nya sama
                if (nameBirthdayReward == birthdayRewardExist && descriptionValue == descriptionRewardExist) {
                    Swal.fire(
                        'Ooops!',
                        'Item yang dipilih sama dengan item sebelumnya.',
                        'warning'
                    );
                    return;
                }


                // samain update birthday reward
                birthdayRewardExist = nameBirthdayReward;

                $.ajax({
                    url: "{{ route('membership/level-membership/update-birthday-reward') }}",
                    type: "PUT",
                    data: {
                        category_id: categoryBirthdayReward,
                        name_birthday_reward: nameBirthdayReward,
                        desc_birthday_reward: descriptionValue,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire(
                                'Berhasil!',
                                response.message,
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Gagal!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        Swal.fire(
                            'Gagal!',
                            'Terjadi kesalahan pada server.',
                            'error'
                        );
                    }
                });
            });

            $('#btn-simpan-exp-reward').off().on('click', function(){
                let categoryExpReward = $('#exp_reward').val();
                let selectedExpReward = $('#exp_reward option:selected').text();
                let splitNameProductCategory = selectedExpReward.split('-');
                let nameExpReward = splitNameProductCategory[0].trim();
                let descExpValue = $('#exp-reward-desc').val();

                if(!categoryExpReward){
                    Swal.fire(
                        'Ooops!',
                        'Item tidak boleh kosong.',
                        'warning'
                    );
                    return;
                }

                if(nameExpReward == expRewardExist && descExpValue == descriptionExpRewardExist){
                    Swal.fire(
                        'Ooops!',
                        'Item yang dipilih sama dengan item sebelumnya.',
                        'warning'
                    );
                    return;
                }

                // samain update exp reward
                expRewardExist = nameExpReward;

                $.ajax({
                    url: "{{ route('membership/level-membership/update-exp-reward') }}",
                    type: "PUT",
                    data: {
                        category_id: categoryExpReward,
                        name_exp_reward: nameExpReward,
                        desc_exp_reward: descExpValue,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response){
                        if (response.status == 'success') {
                            Swal.fire(
                                'Berhasil!',
                                response.message,
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Gagal!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        Swal.fire(
                            'Gagal!',
                            'Terjadi kesalahan pada server.',
                            'error'
                        );
                    }
                });


            });
        </script>
    @endpush
@endsection
