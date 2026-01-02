<button class="btn btn-sm btn-primary pilih-pelanggan-btn"
    data-id="{{ $data->id }}" data-nama="{{ $data->name }}" data-poin="{{$data->point}}"
    data-level="{{ $data->levelMembership->name }}" data-exp="{{ $data->exp }}" data-telfon="{{ $data->telfon }}"
    data-ttl="{{ $data->display_tanggal_lahir }}" data-community="{{ $data->community_id != null ? $data->community->name : '-' }}"
    data-color="{{ $data->levelMembership->color }}" data-created="{{ $data->created_at->format('d M Y') }}"
    data-rewardbirthdaydesc="{{ $reward_birthday->product->description ?? 'Item Birthday Reward belum diset di backoffice' }}"
    data-idproductbirthdayreward="{{ $reward_birthday->product_id ?? '' }}"
    data-canclaim="{{ $data->can_claim_birthday_reward ? '1' : '0' }}"
    data-periodclaim="{{ $data->period_claim }}"
    data-check-claim-reward='@json($check_claim_reward)'>
    Pilih
</button>

<script>
    $('.pilih-pelanggan-btn').off().on('click', function() {
        // console.log($(this).data());

        let id = $(this).data('id');
        let name = $(this).data('nama');
        let point = $(this).data('poin');
        let color = $(this).data('color');
        let bgColor = generateBgFromPrimary(color);
        let created = $(this).data('created');
        let canClaim = $(this).data('canclaim');
        let idProductBirthdayReward = $(this).data('idproductbirthdayreward');
        let rewardBirthdayDesc = $(this).data('rewardbirthdaydesc');
        let periodClaim = $(this).data('periodclaim');
        let claimable = canClaim ? true : false;
        let checkClaimReward = $(this).data('checkClaimReward');

        let tmpCustomer = {
            id: id,
            name: name,
            point: point,
            created: created,
            canClaim: claimable,
            idProductBirthdayReward: idProductBirthdayReward,
            rewardBirthdayDesc: rewardBirthdayDesc,
            periodClaim: periodClaim,
            checkClaimReward: checkClaimReward
        }

        dataPelanggan = tmpCustomer;

        idPelanggan = id;
        pointPelanggan = parseInt(point);
        checkBirthdayReward(idProductBirthdayReward, rewardBirthdayDesc, claimable, false, checkClaimReward);
        $('#modalMemberName').text(name)
        $('#modalLevelBadge').text($(this).data('level'));
        $('#modalLevelBadge').css({
            'background': bgColor,
            'color': color
        });
        $('#modalMemberMeta').text($(this).data('telfon'));
        $('#modalExp').text($(this).data('exp'));
        $('#modalPoints').text(point);
        $('#modalBirthday').text($(this).data('ttl'));
        $('#modalAvatar').text(initials(name));
        $('#member-created').text(created);
        $('#desc-reward-birthday').text(rewardBirthdayDesc);
        $('#birthdayValidity').text(periodClaim);


        // Update button text with Font Awesome icon and name
        $('#pilih-pelanggan').html('<i class="fas fa-user"></i> ' + name);
        $('#treatment-pelanggan').removeClass('d-none');

        const modal = $('#itemModal');
        modal.modal('hide');

        const modalReward = $('#rewardsModal');
        modalReward.modal('show');
    });
</script>
