<button class="btn btn-sm btn-primary pilih-pelanggan-btn"
    data-id="{{ $data->id }}" data-nama="{{ $data->name }}" data-poin="{{$data->point}}"
    data-level="{{ $data->levelMembership->name }}" data-exp="{{ $data->exp }}" data-telfon="{{ $data->telfon }}"
    data-ttl="{{ $data->display_tanggal_lahir }}" data-community="{{ $data->community_id != null ? $data->community->name : '-' }}"
    data-color="{{ $data->levelMembership->color }}" data-created="{{ $data->created_at->format('d M Y') }}"
    data-rewardbirthdaydesc="{{ $reward_birthday->product->description ?? 'Item Birthday Reward belum diset di backoffice' }}"
    data-idproductbirthdayreward="{{ $reward_birthday->product_id ?? '' }}"
    data-canclaim="{{ $data->can_claim_birthday_reward ? '1' : '0' }}"
    data-periode-claim-birthday-reward="{{ $data->period_claim }}"
    data-check-claim-birthday-reward='@json($check_claim_reward_birthday)'
    data-reward-level='@json($reward_level)'
    data-idproductexpreward="{{ $reward_exp->product_id ?? '' }}"
    data-rewardexpdesc="{{ $reward_exp->product->description ?? '' }}"
    data-check-claim-exp-reward='@json($check_claim_reward_exp)'>
    Pilih
</button>

<script>
    $('.pilih-pelanggan-btn').off().on('click', function() {
        // console.log($(this).data());

        resetAllItemRewardInCart();
        let id = $(this).data('id');
        let name = $(this).data('nama');
        let point = $(this).data('poin');
        let exp = $(this).data('exp');
        let color = $(this).data('color');
        let bgColor = generateBgFromPrimary(color);
        let created = $(this).data('created');
        let canClaim = $(this).data('canclaim');
        let idProductBirthdayReward = $(this).data('idproductbirthdayreward');
        let rewardBirthdayDesc = $(this).data('rewardbirthdaydesc');
        let periodeClaimBirthdayReward = $(this).data('periodeClaimBirthdayReward');
        let claimable = canClaim ? true : false;
        let checkClaimBirthdayReward = $(this).data('checkClaimBirthdayReward');
        let rewardLevel = $(this).data('rewardLevel');
        let idProductExpReward = $(this).data('idproductexpreward');
        let rewardExpDesc = $(this).data('rewardexpdesc');
        let checkClaimExpReward = $(this).data('checkClaimExpReward');

        let tmpCustomer = {
            id: id,
            name: name,
            point: point,
            exp: exp,
            created: created,
            canClaim: claimable,
            idProductBirthdayReward: idProductBirthdayReward,
            rewardBirthdayDesc: rewardBirthdayDesc,
            periodeClaimBirthdayReward: periodeClaimBirthdayReward,
            checkClaimBirthdayReward: checkClaimBirthdayReward,
            rewardLevel: rewardLevel,
            idProductExpReward: idProductExpReward,
            rewardExpDesc: rewardExpDesc,
            checkClaimExpReward: checkClaimExpReward
        }

        dataPelanggan = tmpCustomer;

        idPelanggan = id;
        pointPelanggan = parseInt(point);
        checkBirthdayReward(idProductBirthdayReward, rewardBirthdayDesc, claimable, false, checkClaimBirthdayReward);
        checkExpReward(idProductExpReward, rewardExpDesc, checkClaimExpReward);
        generateLevelReward(rewardLevel);
        $('#modalMemberName').text(name)
        $('#modalLevelBadge').text($(this).data('level'));
        $('#modalLevelBadge').css({
            'background': bgColor,
            'color': color
        });
        $('#modalMemberMeta').text($(this).data('telfon'));
        $('#modalExp').text(exp);
        $('#modalPoints').text(point);
        $('#modalBirthday').text($(this).data('ttl'));
        $('#modalAvatar').text(initials(name));
        $('#member-created').text(created);
        $('#desc-reward-birthday').text(rewardBirthdayDesc);
        $('#birthdayValidity').text(periodeClaimBirthdayReward);


        // Update button text with Font Awesome icon and name
        $('#pilih-pelanggan').html('<i class="fas fa-user"></i> ' + name);
        $('#treatment-pelanggan').removeClass('d-none');

        const modal = $('#itemModal');
        modal.modal('hide');

        const modalReward = $('#rewardsModal');
        modalReward.modal('show');
    });
</script>
