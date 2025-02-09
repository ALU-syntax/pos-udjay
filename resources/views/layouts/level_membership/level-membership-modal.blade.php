<x-modal title="Tambah Level Membership" action="{{ $action }}" method="POST">
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
        @if ($data->id)
            <div class="form-group list-reward">
                <label>Reward Memberships <span class="text-danger">*</span></label>
                @foreach ($data->rewards as $reward)
                    <div class="row specific_item">
                        <div class="col-10">
                            <input type="text" hidden value="{{ $reward->id }}" name="id_reward_memberships[]">
                            <input id="reward_memberships" name="reward_memberships[]"
                                value="{{ $reward->name }}" type="text" class="form-control"
                                placeholder="Reward Memberships" required>
                        </div>

                        <div class="col-2 p-0 m-0 d-flex align-items-center justify-content-center">
                            <button type="button"
                                class="btn btn-danger btn-sm remove_specific_item_reward">Remove</button>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <div class="form-group list-reward">
                <label>Reward Memberships <span class="text-danger">*</span></label>
                <input id="reward_memberships" name="reward_memberships[]" value="{{ $data->reward_memberships }}"
                    type="text" class="form-control" placeholder="Reward Memberships" required>
            </div>
        @endif

        <button type="button" class="btn btn-primary w-100" id="add_reward">Add
            Item</button>
    </div>


</x-modal>

<script>
    var _totalSpesificItemPurchaseRequirement = 0;


    // Handle button click to add a new specific item
    $('#add_reward').on('click', function() {
        var newItem = `
            <div class="row specific_item">
                <div class="col-10">
                    <input id="reward_memberships" name="reward_memberships[]" value="{{ $data->reward_memberships }}" type="text"
                    class="form-control" placeholder="Reward Memberships" required>
                </div>
                
                <div class="col-2 p-0 m-0 d-flex align-items-center justify-content-center">
                    <button type="button" class="btn btn-danger btn-sm remove_specific_item_reward">Remove</button>
                </div>
                
            </div>
            
        `;

        $('.list-reward').append(newItem);
    });

    // Handle click event untuk menghapus item tertentu
    $(document).on('click', '.remove_specific_item_reward', function() {
        // Hapus row dengan class `specific_item`
        const specificItem = $(this).closest('.specific_item');

        // Hapus elemen `specific_item`
        specificItem.remove();

    });
</script>
