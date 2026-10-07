const STORAGE_KEY = 'alpha_bridge_students';

export const getStudents = async () => {
  const data = localStorage.getItem(STORAGE_KEY);
  return data ? JSON.parse(data) : [];
};

export const getStudentById = async (id) => {
  const students = await getStudents();
  return students.find(s => s.id === id);
};

export const createStudent = async (studentData) => {
  const students = await getStudents();
  
  if (students.some(s => s.email === studentData.email)) {
    throw new Error('Student with this email already exists');
  }

  const newStudent = {
    id: Date.now().toString(),
    ...studentData,
    status: 'Pending',
    createdAt: new Date().toISOString()
  };
  
  students.push(newStudent);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(students));
  return newStudent;
};

export const updateStudent = async (id, updates) => {
  const students = await getStudents();
  const index = students.findIndex(s => s.id === id);
  if (index === -1) throw new Error('Student not found');
  
  students[index] = { ...students[index], ...updates };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(students));
  return students[index];
};

export const deleteStudent = async (id) => {
  let students = await getStudents();
  students = students.filter(s => s.id !== id);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(students));
  return true;
};