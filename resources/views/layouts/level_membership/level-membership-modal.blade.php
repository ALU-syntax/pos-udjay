<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<x-modal title="{{ $title }}" action="{{ $action }}" method="POST" update="{{ $update }}">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama level membership.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Benchmark <span class="text-danger">*</span></label>
            <input id="benchmark" name="benchmark" value="{{ $data->benchmark }}" type="number" min="0"
                class="form-control" placeholder="benchmark" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Color <span class="text-danger">*</span></label>
            <input id="color" name="color" value="{{ $data->color }}" type="color" class="form-control"
                style="height: 40px !important;" required>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="form-check">
            <label class="form-check-label" for="without_reward">Tanpa Reward </label>
            <input class="form-check-input" type="checkbox" name="without_reward" value="1" id="without_reward"
                onchange="handleWithReward(this)">
        </div>
    </div>

    <div class="col-sm-12" id="column-list-reward">
        @if ($data->id)
            <div class="container list-reward">
                @foreach ($data->rewards as $reward)
                    <div class="row reward specific_item mt-2">
                        <div class="col-10 pe-0">
                            <div class="row">
                                <div class="form-group col-8">
                                    <input type="text" value="{{ $reward->name }}" hidden name="name_product[]">
                                    <input type="text" hidden value="{{ $reward->id }}"
                                        name="id_reward_memberships[]">
                                    <label for="product_id">Reward Memberships </label>
                                    <select name="category_product_id[]"
                                        class="select2InsideModal category_product_id form-select w-100"
                                        style="width: 100% !important;">
                                        <option disabled>Pilih Reward</option>
                                        @foreach (json_decode($product_rewards) as $productRewards)
                                            <option value="{{ $productRewards->category_id }}"
                                                @if ($productRewards->name === $reward->name) selected @endif>
                                                {{ $productRewards->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4 form-group">
                                    <label for="icon">Icon</label>
                                    <select name="icon[]" value="{{ $data->icon }}"
                                        class="select2Icon form-select w-100" style="width: 100% !important;">
                                        <option disabled>Pilih Icon</option>
                                        @foreach (config('fontawesome') as $class => $label)
                                            <option value="{{ $class }}"
                                                {{ $reward->icon === $class ? 'selected' : '' }}>
                                                <i class="{{ $class }}"></i>{{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mt-1">
                                    <textarea name="level-reward-desc[]" rows="4" class="form-control w-100"
                                        placeholder="Description reward, Contoh: Gratis 1 menu 'Signature Drink'">{{ $reward->description }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-2 mt-3 d-flex">
                            <div>
                                <button type="button"
                                    class="btn btn-danger btn-sm w-100 h-100 remove_specific_item_reward">Remove</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="container list-reward">
                <div class="row reward">
                    <div class="form-group col-8">
                        <input type="text" value="{{ json_decode($product_rewards[0])->name }}" hidden
                            name="name_product[]">
                        <label for="product_id">Reward Memberships </label>
                        <select name="category_product_id[]"
                            class="select2InsideModal category_product_id form-select w-100"
                            style="width: 100% !important;">
                            <option disabled>Pilih Reward</option>
                            @foreach (json_decode($product_rewards) as $productRewards)
                                <option value="{{ $productRewards->category_id }}">
                                    {{ $productRewards->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 form-group">
                        <label for="icon">Icon</label>
                        <select name="icon[]" value="{{ $data->icon }}" class="select2Icon form-select w-100"
                            style="width: 100% !important;">
                            <option disabled>Pilih Icon</option>
                            {{-- @foreach (config('fontawesome') as $class => $label)
                                <option value="{{ $class }}">
                                    <i class=" {{ $class }}"></i>{{ $label }}
                                </option>
                            @endforeach --}}
                        </select>
                    </div>
                    <div class="col-12 mt-1">
                        <textarea name="level-reward-desc[]" rows="4" class="form-control w-100"
                            placeholder="Description reward, Contoh: Gratis 1 menu 'Signature Drink'"></textarea>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 form-group">
            <button type="button" class="btn btn-primary w-100 mt-2" id="add_reward">Add
                Item</button>
        </div>
    </div>


</x-modal>

<script>
    var _totalSpesificItemPurchaseRequirement = 0;

    function formatIcon(icon) {
        if (!icon.id) {
            return icon.text;
        }
        var $icon = $(
            `<span><i class="fas ${icon.id}"></i> ${icon.text}</span>`
        );
        return $icon;
    }

    function handleWithReward(widget) {
        $widget = $(widget);
        $columnReward = $('#column-list-reward');

        if ($widget.is(':checked')) {
            $columnReward.addClass('d-none');
        } else {
            $columnReward.removeClass('d-none');
        }
    }

    function initSelect2InsideModal() {
        $('.select2InsideModal').not('.select2-hidden-accessible').select2({
            dropdownParent: $('#modal_action .modal-body'),
            templateResult: formatIcon,
            templateSelection: formatIcon,
            placeholder: 'Pilih Reward',
            width: '100%'
        });

        $('.select2Icon').not('.select2-hidden-accessible').select2({
            dropdownParent: $('#modal_action .modal-body'),
            placeholder: 'Pilih Icon',
            width: '100%',
            ajax: {
                url: "{{ route('membership/level-membership/searchIcon') }}",
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function(data) {
                    return data;
                }
            },
            templateResult: formatIcon,
            templateSelection: formatIcon
        });

    }

    $(document).ready(function() {
        initSelect2InsideModal();
        $('#without_reward').prop('checked', false);
        $('#column-list-reward').removeClass('d-none');

        @if ($data->rewards->isEmpty() || $data->rewards[0]->name == '' || $data->rewards[0]->name == '-')
            $('#without_reward').prop('checked', true);
            $('#column-list-reward').addClass('d-none');
        @endif
        // Handle click event untuk menghapus item tertentu
        $(document).on('click', '.remove_specific_item_reward', function() {
            // Hapus row dengan class `specific_item`
            const specificItem = $(this).closest('.specific_item');

            // destroy select2 dulu
            specificItem.find('.select2InsideModal').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            // Hapus elemen `specific_item`
            specificItem.remove();

            //kalo ga ada reward auto checked without_reward
            if($('.reward').length == 0){
                $('#without_reward').prop('checked', true);
                $('#column-list-reward').addClass('d-none');
            }else{
                $('#without_reward').prop('checked', false);
                $('#column-list-reward').removeClass('d-none');
            }
        });

        $(document).on('change', '.category_product_id', function() {
            var selectedOption = $(this).find('option:selected');
            var selectedText = selectedOption.text();

            // Set the value of the hidden input field
            $(this).siblings('input[name="name_product[]"]').val(selectedText);
        });

        $('#add_reward').on('click', function() {
            let checkProductReward = @json($product_rewards);
            let firstNameProduct = checkProductReward.length ? checkProductReward[0].name : "";
            var newItem = `
            <div class="row specific_item mt-2">
                <div class="col-10 pe-0">
                    <div class="row reward">
                        <div class="form-group col-8">
                            <input type="text" value="${firstNameProduct}"  hidden name="name_product[]">
                            <select name="category_product_id[]"
                                class="select2InsideModal category_product_id form-select w-100"
                                style="width: 100% !important;">
                                <option disabled>Pilih Reward</option>
                                @foreach (json_decode($product_rewards) as $productRewards)
                                    <option value="{{ $productRewards->category_id }}">
                                        {{ $productRewards->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 form-group">
                            <select name="icon[]" value="{{ $data->icon }}"
                                class="select2Icon form-select w-100" style="width: 100% !important;">
                                <option disabled>Pilih Icon</option>
                                @foreach (config('fontawesome') as $class => $label)
                                    <option value="{{ $class }}" {{ $data->icon === $class ? 'selected' : '' }}>
                                        <i class="fa-solid {{ $class }}"></i>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mt-1">
                            <textarea name="level-reward-desc[]" rows="4" class="form-control w-100"
                                placeholder="Description reward, Contoh: Gratis 1 menu 'Signature Drink'"></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-2 mt-3 d-flex">
                    <div>
                         <button type="button" class="btn btn-danger btn-sm w-100 h-100 remove_specific_item_reward">Remove</button>
                     </div>
                </div>
            </div>

        `;

            $('.list-reward').append(newItem);
            initSelect2InsideModal();
        });

    });
</script>
