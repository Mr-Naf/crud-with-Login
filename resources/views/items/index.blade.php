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
                        <!-- filled by AJAX -->
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
            $(function() {
                // Setup CSRF for all AJAX
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    }
                });


                // Load items on page load
                loadItems();

                // Open modal for create
                $('#createNewItem').click(function() {
                    clearForm();
                    $('#modalTitle').text('Add Item');
                    $('#saveBtn').data('action', 'create');
                    $('#ajaxModal').modal('show');
                });

                // Submit form (create/update)
                $('#itemForm').on('submit', function(e) {
                    e.preventDefault();
                    $('.text-danger').remove(); // clear old validation messages

                    var action = $('#saveBtn').data('action') || 'create';
                    var itemId = $('#item_id').val();
                    var url = action === 'create' ? "{{ route('items.store') }}" : '/items/' + itemId;
                    var type = action === 'create' ? 'POST' : 'PUT';

                    var data = {
                        name: $('#name').val(),
                        description: $('#description').val(),
                        price: $('#price').val()
                    };

                    // Show loading state
                    $('#saveBtn').prop('disabled', true).text('Saving...');

                    $.ajax({
                            url: url,
                            type: type,
                            data: data,
                            dataType: 'json'
                        })
                        .done(function(res) {
                            $('#ajaxModal').modal('hide');
                            clearForm();
                            loadItems();

                            // SweetAlert2 success toast
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        })
                        .fail(function(xhr) {
                            if (xhr.status === 422) {
                                // Show validation errors below input fields
                                var errors = xhr.responseJSON.errors;
                                $.each(errors, function(k, v) {
                                    $('#' + k).after('<div class="text-danger mt-1">' + v[0] +
                                        '</div>');
                                });

                                // SweetAlert2 error toast for validation
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Please fix the validation errors',
                                    timer: 2500,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                });
                            } else {
                                // SweetAlert2 error toast for general errors
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Something went wrong. Please try again.',
                                    timer: 2500,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                });
                            }
                        })
                        .always(function() {
                            $('#saveBtn').prop('disabled', false).text('Save');
                        });
                });

                // Click Edit -> load item and show modal
                $(document).on('click', '.editItem', function() {
                    var id = $(this).data('id');
                    $.get('/items/' + id)
                        .done(function(data) {
                            clearForm();
                            $('#modalTitle').text('Edit Item');
                            $('#item_id').val(data.id);
                            $('#name').val(data.name);
                            $('#description').val(data.description);
                            $('#price').val(data.price);
                            $('#saveBtn').data('action', 'update');
                            $('#ajaxModal').modal('show');
                        })
                        .fail(function() {
                            showMessage('Could not fetch item data.', 'danger');
                        });
                });

                // Delete
                $(document).on('click', '.deleteItem', function() {
                    var id = $(this).data('id');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This item will be permanently deleted!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proceed with AJAX delete
                            $.ajax({
                                    url: '/items/' + id,
                                    type: 'DELETE'
                                })
                                .done(function(res) {
                                    loadItems();
                                    Swal.fire('Deleted!', res.message, 'success');
                                })
                                .fail(function() {
                                    Swal.fire('Error!', 'Delete failed.', 'error');
                                });
                        }
                    });
                });

                // helper: load items list
                function loadItems() {
                    $.ajax({
                            url: "{{ route('items.index') }}",
                            type: 'GET',
                            dataType: 'json'
                        })
                        .done(function(data) {
                            var rows = '';
                            if (data.length === 0) {
                                rows = '<tr><td colspan="5" class="text-center">No items found.</td></tr>';
                            } else {
                                $.each(data, function(i, item) {
                                    rows += '<tr>' +
                                        '<td>' + item.id + '</td>' +
                                        '<td>' + escapeHtml(item.name) + '</td>' +
                                        '<td>' + (item.description ? escapeHtml(item.description) : '') +
                                        '</td>' +
                                        '<td>' + (item.price !== null ? item.price : '') + '</td>' +
                                        '<td>' +
                                        '<button data-id="' + item.id +
                                        '" class="btn btn-sm btn-primary me-1 editItem">Edit</button>' +
                                        '<button data-id="' + item.id +
                                        '" class="btn btn-sm btn-danger deleteItem">Delete</button>' +
                                        '</td>' +
                                        '</tr>';
                                });
                            }
                            $('#itemsTableBody').html(rows);
                        })
                        .fail(function() {
                            $('#itemsTableBody').html(
                                '<tr><td colspan="5" class="text-center text-danger">Failed to load items.</td></tr>'
                            );
                            showMessage('Failed to load items.', 'danger');
                        });
                }

                // helper: clear form and errors
                function clearForm() {
                    $('#itemForm')[0].reset();
                    $('#item_id').val('');
                    $('.text-danger').remove();
                }

                // simple html escape
                function escapeHtml(text) {
                    return $('<div>').text(text).html();
                }

                // helper: show bootstrap alert
                function showMessage(message, type = 'success') {
                    var alertHtml = `
            <div class="alert alert-` + type + ` alert-dismissible fade show" role="alert">
              ` + message + `
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
                    $('#alertArea').html(alertHtml);

                    // Auto close after 3 sec
                    setTimeout(() => {
                        $('.alert').alert('close');
                    }, 3000);
                }
            });
        </script>
    @endpush
</x-app-layout>
