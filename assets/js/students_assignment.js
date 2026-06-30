// assets/js/student_assignment.js
// Admin assigns/changes a student's currentHostelId (occupancy tracking)

async function loadStudentAssignments() {
  const tbody = document.getElementById('studentAssignTableBody');
  if (!tbody) return;

  try {
    const res  = await fetch('/SU-Housing/api/students.php');
    const data = await res.json();

    if (!res.ok) {
      tbody.innerHTML = `<tr><td colspan="4">Failed to load students.</td></tr>`;
      return;
    }

    if (!data.students.length) {
      tbody.innerHTML = `<tr><td colspan="4">No students registered yet.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.students.map(s => `
      <tr>
        <td>${escapeHtml(s.fullName)}</td>
        <td>${escapeHtml(s.admissionNumber)}</td>
        <td>${s.currentHostelName ? escapeHtml(s.currentHostelName) : '<span class="badge badge-gray">Unassigned</span>'}</td>
        <td>
          <select class="form-control assign-select" data-student-id="${s.studentId}"
                  style="width:auto; padding:6px 10px; font-size:13px;">
            <option value="">— Unassigned —</option>
            ${window.HOSTEL_OPTIONS.map(h => `
              <option value="${h.hostelId}" ${s.currentHostelId == h.hostelId ? 'selected' : ''}>
                ${escapeHtml(h.hostelName)}
              </option>
            `).join('')}
          </select>
        </td>
      </tr>
    `).join('');

    document.querySelectorAll('.assign-select').forEach(sel => {
      sel.addEventListener('change', () => assignStudent(sel.dataset.studentId, sel.value));
    });

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4">Network error loading students.</td></tr>`;
  }
}

async function assignStudent(studentId, hostelId) {
  try {
    const res = await fetch(`/SU-Housing/api/students.php?id=${studentId}`, {
      method:  'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ hostelId: hostelId || null }),
    });
    const data = await res.json();

    if (res.ok) {
      showToast(data.message, 'success');
    } else {
      showToast(data.error || 'Failed to update assignment.', 'error');
    }
  } catch (err) {
    showToast('Network error. Please try again.', 'error');
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', loadStudentAssignments);