<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Items
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="font-semibold">Items</h2>
            <button id="createNewItem" class="btn btn-success  shadow">+ Add Item</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width:60px">ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th style="width:120px">Price</th>
                            <th style="width:170px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                       @foreach ($items as $item)
                          <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->price }}</td>
                            <td>
                             <button data-id="{{ $item->id }}" class="btn btn-sm btn-primary me-1 editItem">Edit</button>
                             <button data-id="{{ $item->id }}" class="btn btn-sm btn-danger deleteItem">Delete</button>
                            </td>
                          </tr>


                       @endforeach
                    </tbody>
                </table>
                <div id="alertArea"></div>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="itemForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="item_id" name="item_id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" id="price" name="price" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {

                // ========== SETUP: Configure CSRF token for all AJAX requests ==========
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    }
                });

                // ========== ADD NEW ITEM: Open modal when "Add Item" button is clicked ==========
                $('#createNewItem').click(function() {
                    // Clear the form and reset everything
                    $('#itemForm')[0].reset();
                    $('#item_id').val('');
                    $('.text-danger').remove();

                    // Set modal title and show modal
                    $('#modalTitle').text('Add Item');
                    $('#saveBtn').data('action', 'create');
                    $('#ajaxModal').modal('show');
                });

                // ========== SAVE ITEM: Handle form submission (both create and update) ==========
                $('#itemForm').on('submit', function(e) {
                    e.preventDefault();
                    $('.text-danger').remove(); // Remove old validation error messages

                    // Get the action (create or update) and prepare data
                    var action = $('#saveBtn').data('action') || 'create';
                    var itemId = $('#item_id').val();
                    var url = action === 'create' ? "{{ route('items.store') }}" : '/items/' + itemId;
                    var type = action === 'create' ? 'POST' : 'PUT';

                    // Get form data
                    var formData = {
                        name: $('#name').val(),
                        description: $('#description').val(),
                        price: $('#price').val()
                    };

                    // Show loading state on save button
                    $('#saveBtn').prop('disabled', true).text('Saving...');

                    // Send AJAX request to server
                    $.ajax({
                        url: url,
                        type: type,
                        data: formData,
                        dataType: 'json'
                    })
                    .done(function(response) {
                        // Hide the modal first
                        $('#ajaxModal').modal('hide');

                        // Get item data from response (handle different response formats)
                        var item = response.item || response.data || response;
                        var message = response.message || 'Operation completed successfully';

                        if (action === 'create') {
                            // CREATE: Add new row to the table
                            var itemData = {
                                id: item.id || 'New',
                                name: item.name || $('#name').val(),
                                description: item.description || $('#description').val(),
                                price: item.price || $('#price').val()
                            };

                            // Build new row HTML and append to table
                            var newRow = '<tr>' +
                                '<td>' + itemData.id + '</td>' +
                                '<td>' + itemData.name + '</td>' +
                                '<td>' + (itemData.description || '') + '</td>' +
                                '<td>' + (itemData.price || '') + '</td>' +
                                '<td>' +
                                '<button data-id="' + itemData.id + '" class="btn btn-sm btn-primary me-1 editItem">Edit</button>' +
                                '<button data-id="' + itemData.id + '" class="btn btn-sm btn-danger deleteItem">Delete</button>' +
                                '</td>' +
                                '</tr>';
                            $('#itemsTableBody').append(newRow);

                            // Show success message with SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Item created successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Item updated successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    })
                    .fail(function(xhr) {
                        console.log('Error:', xhr);

                        if (xhr.status === 422) {
                            // VALIDATION ERRORS: Show validation errors below input fields
                            var errors = xhr.responseJSON?.errors || {};
                            $.each(errors, function(field, messages) {
                                $('#' + field).after('<div class="text-danger mt-1">' + messages[0] + '</div>');
                            });

                            // Show validation error message with SweetAlert
                            Swal.fire({
                                icon: 'warning',
                                title: 'Validation Error',
                                text: 'Please fix the errors and try again.'
                            });
                        } else if (xhr.status === 500) {
                            // SERVER ERROR: Show server error message
                            console.error('Server Response:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'Something went wrong on the server. Please try again.'
                            });
                        } else {
                            // OTHER ERRORS: Show general error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong. Status: ' + xhr.status
                            });
                        }
                    })
                    .always(function() {
                        // Always restore the save button to normal state
                        $('#saveBtn').prop('disabled', false).text('Save');
                    });
                });

                // ========== EDIT ITEM: Load item data and show modal when "Edit" button is clicked ==========
                $(document).on('click', '.editItem', function() {
                    var itemId = $(this).data('id');

                    // Fetch item data from server
                    $.get('/items/' + itemId)
                    .done(function(itemData) {
                        // Clear form and fill with item data
                        $('#itemForm')[0].reset();
                        $('#item_id').val('');
                        $('.text-danger').remove();

                        // Set modal title and fill form fields
                        $('#modalTitle').text('Edit Item');
                        $('#item_id').val(itemData.id);
                        $('#name').val(itemData.name);
                        $('#description').val(itemData.description);
                        $('#price').val(itemData.price);
                        $('#saveBtn').data('action', 'update');

                        // Show the modal
                        $('#ajaxModal').modal('show');
                    })
                    .fail(function() {
                        // Show error message if item data cannot be loaded
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not load item data. Please try again.'
                        });
                    });
                });

                // ========== DELETE ITEM: Confirm and delete item when "Delete" button is clicked ==========
                $(document).on('click', '.deleteItem', function() {
                    var itemId = $(this).data('id');
                    var tableRow = $(this).closest('tr');

                    // Show confirmation dialog with SweetAlert
                    Swal.fire({
                        title: 'Delete Item?',
                        text: "This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // User confirmed, proceed with deletion
                            $.ajax({
                                url: '/items/' + itemId,
                                type: 'DELETE'
                            })
                            .done(function(response) {
                                // Remove the row from table
                                tableRow.remove();

                                // Show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Item has been deleted successfully.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            })
                            .fail(function() {
                                // Show error message if deletion failed
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to delete item. Please try again.'
                                });
                            });
                        }
                    });
                });

            });
        </script>
    @endpush
</x-app-layout>
