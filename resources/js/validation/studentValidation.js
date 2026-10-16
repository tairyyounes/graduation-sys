// Client-side mirror of the server rules for student accounts (app/Support/StudentAccount.php,
// AddingUserRequest, StudentImportController). Used for live validation while typing.

export const STUDENT_EMAIL_DOMAIN = 'cctt.edu.ly'

const STUDENT_NUMBER_RE = /^\d{6}$/
const FULL_NAME_RE = /^[\p{L}\s]+$/u
const EMAIL_RE = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/

/** The official email for a student number, or '' while there is no number yet. */
export const studentEmailFor = (studentNumber) => {
  const number = String(studentNumber ?? '').trim()
  return number ? `${number}@${STUDENT_EMAIL_DOMAIN}` : ''
}

export const emailMatchesStudentNumber = (email, studentNumber) =>
  String(email ?? '').trim().toLowerCase() === studentEmailFor(studentNumber)

/**
 * Validates one student's fields. Returns { field: [message] } for every invalid field (empty object when valid).
 * `fields` limits which fields are checked (default: all of them).
 */
export const validateStudent = (student, t, fields = ['student_number', 'full_name', 'email', 'semester']) => {
  const errors = {}
  const number = String(student.student_number ?? '').trim()
  const name = String(student.full_name ?? '').trim()
  const email = String(student.email ?? '').trim()

  if (fields.includes('student_number')) {
    if (!number) errors.student_number = [t('validation.student.number_required')]
    else if (!STUDENT_NUMBER_RE.test(number)) errors.student_number = [t('validation.student.number_digits')]
  }

  if (fields.includes('full_name')) {
    if (!name) errors.full_name = [t('validation.student.name_required')]
    else if (!FULL_NAME_RE.test(name)) errors.full_name = [t('validation.student.name_letters')]
  }

  if (fields.includes('email')) {
    if (!email || !EMAIL_RE.test(email)) errors.email = [t('validation.student.email_invalid')]
    else if (STUDENT_NUMBER_RE.test(number) && !emailMatchesStudentNumber(email, number)) {
      errors.email = [t('validation.student.email_mismatch')]
    }
  }

  if (fields.includes('semester') && student.semester !== undefined && student.semester !== null && student.semester !== '') {
    const semester = Number(student.semester)
    if (!Number.isInteger(semester) || semester < 1 || semester > 8) {
      errors.semester = [t('validation.student.semester_range')]
    }
  }

  return errors
}
