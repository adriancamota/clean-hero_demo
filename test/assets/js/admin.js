// Update search functionality to work with current tab
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'users';
    window.location.href = `admin.php?tab=${currentTab}&search=${encodeURIComponent(e.target.value)}`;
});

// Print functionality
function printTable() {
    window.print();
}

// Export CSV functionality
function exportCSV() {
    const table = document.getElementById('dataTable');
    const rows = table.getElementsByTagName('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td').length > 0 
            ? row.getElementsByTagName('td') 
            : row.getElementsByTagName('th');
        let csvRow = [];
        
        for (let j = 0; j < cells.length; j++) {
            // Skip action buttons
            if (!cells[j].querySelector('.action-buttons')) {
                csvRow.push('"' + cells[j].textContent.trim().replace(/"/g, '""') + '"');
            }
        }
        
        csv.push(csvRow.join(','));
    }
    
    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'users';
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `${currentTab}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Edit user functionality
function editUser(userId) {
    editRecord('users', userId);
}

// Add this function for editing records
function editRecord(table, id) {
    window.location.href = `edit_record.php?table=${table}&id=${id}`;
} 