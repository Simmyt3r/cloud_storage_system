document.addEventListener('DOMContentLoaded', function() {
    // --- Right-Click Context Menu ---
    const contextMenu = createContextMenu();
    let currentItem = null;

    document.querySelectorAll('.file-table tr[data-id], .grid-item[data-id]').forEach(item => {
        item.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            currentItem = this;
            const type = this.dataset.type;
            updateContextMenu(type);
            contextMenu.style.top = `${e.clientY}px`;
            contextMenu.style.left = `${e.clientX}px`;
            contextMenu.style.display = 'block';
        });
    });

    window.addEventListener('click', () => {
        contextMenu.style.display = 'none';
    });

    function createContextMenu() {
        const menu = document.createElement('ul');
        menu.className = 'context-menu';
        document.body.appendChild(menu);
        return menu;
    }

    function updateContextMenu(type) {
        contextMenu.innerHTML = ''; // Clear previous items
        const actions = (type === 'folder')
            ? ['Open', 'Rename', 'Delete']
            : ['View', 'Download', 'Rename', 'Delete'];

        actions.forEach(action => {
            const li = document.createElement('li');
            li.textContent = action;
            li.dataset.action = action.toLowerCase();
            if (action === 'Delete') {
                li.classList.add('delete');
            }
            contextMenu.appendChild(li);
        });
    }

    contextMenu.addEventListener('click', function(e) {
        if (e.target.tagName === 'LI' && currentItem) {
            const action = e.target.dataset.action;
            const id = currentItem.dataset.id;
            // Add your logic for each action here
            alert(`${action} on ${currentItem.dataset.type} with ID ${id}`);
        }
    });


    // --- Drag and Drop File Upload ---
    const dropZone = document.getElementById('drop-zone');
    const uploadForm = document.getElementById('upload-form');

    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0 && uploadForm) {
                uploadForm.querySelector('input[type="file"]').files = files;
                handleFiles(files);
            }
        });
    }

    function handleFiles(files) {
        const fileList = document.getElementById('file-list-display');
        if (!fileList) return;
        fileList.innerHTML = '';

        [...files].forEach(file => {
            const fileElement = document.createElement('div');
            fileElement.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
            
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            const fill = document.createElement('div');
            fill.className = 'progress-bar-fill';
            progressBar.appendChild(fill);
            fileElement.appendChild(progressBar);
            fileList.appendChild(fileElement);

            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                fill.style.width = progress + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, 200);
        });
    }


    // --- Search Filtering ---
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const items = document.querySelectorAll('.file-table tr[data-name], .grid-item[data-name]');
            items.forEach(item => {
                const name = item.dataset.name.toLowerCase();
                if (name.includes(filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }


    // --- Multi-select with Checkboxes ---
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const checkboxes = document.querySelectorAll('.item-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsBar);
    });

    function updateBulkActionsBar() {
        const selectedCount = document.querySelectorAll('.item-checkbox:checked').length;
        if (selectedCount > 0) {
            bulkActionsBar.querySelector('#selected-count').textContent = selectedCount;
            bulkActionsBar.style.display = 'flex';
        } else {
            bulkActionsBar.style.display = 'none';
        }
    }
});