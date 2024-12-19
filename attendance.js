// JavaScript function to filter the attendance list based on student ID
function filterAttendance() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("search_student_id");
    filter = input.value.toUpperCase();
    table = document.getElementById("attendance_table");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[1]; // Column 1 is Student ID
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
