using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using Newtonsoft.Json;

namespace StudentsApp
{
    public partial class MainForm : Form
    {
        private BindingList<Student> students = new BindingList<Student>();
        private string currentFilePath = string.Empty;
        private bool isModified = false;

        public MainForm()
        {
            InitializeComponent();
            SetupDataGridView();
            SetupControls();
            LoadSampleData();
        }

        private void SetupDataGridView()
        {
            dataGridView.AutoGenerateColumns = false;
            dataGridView.DataSource = students;

            // Настройка столбцов
            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "LastName",
                HeaderText = "Фамилия",
                Name = "colLastName"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "FirstName",
                HeaderText = "Имя",
                Name = "colFirstName"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "MiddleName",
                HeaderText = "Отчество",
                Name = "colMiddleName"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "Course",
                HeaderText = "Курс",
                Name = "colCourse"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "Group",
                HeaderText = "Группа",
                Name = "colGroup"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "BirthDate",
                HeaderText = "Дата рождения",
                Name = "colBirthDate"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "Email",
                HeaderText = "Email",
                Name = "colEmail"
            });

            dataGridView.Columns.Add(new DataGridViewTextBoxColumn()
            {
                DataPropertyName = "Phone",
                HeaderText = "Телефон",
                Name = "colPhone"
            });

            dataGridView.SelectionChanged += DataGridView_SelectionChanged;
        }

        private void SetupControls()
        {
            // Настройка DateTimePicker для даты рождения
            dateTimePickerBirthDate.Format = DateTimePickerFormat.Custom;
            dateTimePickerBirthDate.CustomFormat = "dd.MM.yyyy";
            dateTimePickerBirthDate.MinDate = new DateTime(1991, 12, 25);
            dateTimePickerBirthDate.MaxDate = DateTime.Today;

            // Настройка ComboBox для курса
            comboBoxCourse.Items.AddRange(new object[] { 1, 2, 3, 4, 5, 6 });

            // Привязка событий для проверки изменений
            textBoxLastName.TextChanged += (s, e) => isModified = true;
            textBoxFirstName.TextChanged += (s, e) => isModified = true;
            textBoxMiddleName.TextChanged += (s, e) => isModified = true;
            comboBoxCourse.SelectedIndexChanged += (s, e) => isModified = true;
            textBoxGroup.TextChanged += (s, e) => isModified = true;
            dateTimePickerBirthDate.ValueChanged += (s, e) => isModified = true;
            textBoxEmail.TextChanged += (s, e) => isModified = true;
            textBoxPhone.TextChanged += (s, e) => isModified = true;

            // Настройка маски для телефона
            textBoxPhone.Text = "+7-";
        }

        private void LoadSampleData()
        {
            students.Add(new Student
            {
                LastName = "Иванов",
                FirstName = "Иван",
                MiddleName = "Иванович",
                Course = 1,
                Group = "ИТ-101",
                BirthDate = new DateTime(2000, 5, 15),
                Email = "ivanov@gmail.com",
                Phone = "+7-123-456-78-90"
            });

            students.Add(new Student
            {
                LastName = "Петрова",
                FirstName = "Мария",
                MiddleName = "Сергеевна",
                Course = 2,
                Group = "ИТ-202",
                BirthDate = new DateTime(1999, 8, 22),
                Email = "petrova@yandex.ru",
                Phone = "+7-987-654-32-10"
            });
        }

        private void DataGridView_SelectionChanged(object sender, EventArgs e)
        {
            if (dataGridView.SelectedRows.Count > 0)
            {
                var selectedStudent = dataGridView.SelectedRows[0].DataBoundItem as Student;
                if (selectedStudent != null)
                {
                    textBoxLastName.Text = selectedStudent.LastName;
                    textBoxFirstName.Text = selectedStudent.FirstName;
                    textBoxMiddleName.Text = selectedStudent.MiddleName;
                    comboBoxCourse.SelectedItem = selectedStudent.Course;
                    textBoxGroup.Text = selectedStudent.Group;
                    dateTimePickerBirthDate.Value = selectedStudent.BirthDate;
                    textBoxEmail.Text = selectedStudent.Email;
                    textBoxPhone.Text = selectedStudent.Phone;
                }
            }
        }

        private void buttonAdd_Click(object sender, EventArgs e)
        {
            if (ValidateForm())
            {
                var student = new Student
                {
                    LastName = textBoxLastName.Text,
                    FirstName = textBoxFirstName.Text,
                    MiddleName = textBoxMiddleName.Text,
                    Course = (int)comboBoxCourse.SelectedItem,
                    Group = textBoxGroup.Text,
                    BirthDate = dateTimePickerBirthDate.Value,
                    Email = textBoxEmail.Text,
                    Phone = textBoxPhone.Text
                };

                students.Add(student);
                isModified = true;
                ClearForm();
            }
        }

        private void buttonUpdate_Click(object sender, EventArgs e)
        {
            if (dataGridView.SelectedRows.Count > 0 && ValidateForm())
            {
                var selectedStudent = dataGridView.SelectedRows[0].DataBoundItem as Student;
                if (selectedStudent != null)
                {
                    selectedStudent.LastName = textBoxLastName.Text;
                    selectedStudent.FirstName = textBoxFirstName.Text;
                    selectedStudent.MiddleName = textBoxMiddleName.Text;
                    selectedStudent.Course = (int)comboBoxCourse.SelectedItem;
                    selectedStudent.Group = textBoxGroup.Text;
                    selectedStudent.BirthDate = dateTimePickerBirthDate.Value;
                    selectedStudent.Email = textBoxEmail.Text;
                    selectedStudent.Phone = textBoxPhone.Text;

                    dataGridView.Refresh();
                    isModified = true;
                }
            }
        }

        private void buttonDelete_Click(object sender, EventArgs e)
        {
            if (dataGridView.SelectedRows.Count > 0)
            {
                var result = MessageBox.Show("Вы уверены, что хотите удалить выбранного студента?",
                    "Подтверждение удаления", MessageBoxButtons.YesNo, MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    var selectedStudent = dataGridView.SelectedRows[0].DataBoundItem as Student;
                    students.Remove(selectedStudent);
                    isModified = true;
                    ClearForm();
                }
            }
        }

        private void buttonClear_Click(object sender, EventArgs e)
        {
            ClearForm();
        }

        private void ClearForm()
        {
            textBoxLastName.Text = string.Empty;
            textBoxFirstName.Text = string.Empty;
            textBoxMiddleName.Text = string.Empty;
            comboBoxCourse.SelectedIndex = -1;
            textBoxGroup.Text = string.Empty;
            dateTimePickerBirthDate.Value = DateTime.Today;
            textBoxEmail.Text = string.Empty;
            textBoxPhone.Text = "+7-";
        }

        private bool ValidateForm()
        {
            bool isValid = true;
            errorProvider.Clear();

            if (string.IsNullOrWhiteSpace(textBoxLastName.Text))
            {
                errorProvider.SetError(textBoxLastName, "Фамилия обязательна для заполнения");
                textBoxLastName.Focus();
                isValid = false;
            }

            if (string.IsNullOrWhiteSpace(textBoxFirstName.Text))
            {
                errorProvider.SetError(textBoxFirstName, "Имя обязательно для заполнения");
                if (isValid) textBoxFirstName.Focus();
                isValid = false;
            }

            if (comboBoxCourse.SelectedIndex == -1)
            {
                errorProvider.SetError(comboBoxCourse, "Выберите курс");
                if (isValid) comboBoxCourse.Focus();
                isValid = false;
            }

            if (string.IsNullOrWhiteSpace(textBoxGroup.Text))
            {
                errorProvider.SetError(textBoxGroup, "Группа обязательна для заполнения");
                if (isValid) textBoxGroup.Focus();
                isValid = false;
            }

            // Проверка email
            if (string.IsNullOrWhiteSpace(textBoxEmail.Text))
            {
                errorProvider.SetError(textBoxEmail, "Email обязателен для заполнения");
                if (isValid) textBoxEmail.Focus();
                isValid = false;
            }
            else if (!IsValidEmail(textBoxEmail.Text))
            {
                errorProvider.SetError(textBoxEmail, "Неверный формат email. Допустимые домены: yandex.ru, gmail.com, icloud.com");
                if (isValid) textBoxEmail.Focus();
                isValid = false;
            }

            // Проверка телефона
            if (string.IsNullOrWhiteSpace(textBoxPhone.Text))
            {
                errorProvider.SetError(textBoxPhone, "Телефон обязателен для заполнения");
                if (isValid) textBoxPhone.Focus();
                isValid = false;
            }
            else if (!IsValidPhone(textBoxPhone.Text))
            {
                errorProvider.SetError(textBoxPhone, "Неверный формат телефона. Используйте формат: +7-XXX-XXX-XX-XX");
                if (isValid) textBoxPhone.Focus();
                isValid = false;
            }

            return isValid;
        }

        private bool IsValidEmail(string email)
        {
            try
            {
                var mailRegex = new Regex(@"^[^@]{3,}@(yandex\.ru|gmail\.com|icloud\.com)$");
                return mailRegex.IsMatch(email);
            }
            catch
            {
                return false;
            }
        }

        private bool IsValidPhone(string phone)
        {
            var phoneRegex = new Regex(@"^\+7-\d{3}-\d{3}-\d{2}-\d{2}$");
            return phoneRegex.IsMatch(phone);
        }

        private void buttonSort_Click(object sender, EventArgs e)
        {
            var sortForm = new SortForm();
            if (sortForm.ShowDialog() == DialogResult.OK)
            {
                var sortOption = sortForm.SelectedSortOption;
                var sortDirection = sortForm.SortDirection;

                List<Student> sortedList = null;

                switch (sortOption)
                {
                    case SortOption.LastName:
                        sortedList = sortDirection == SortDirection.Ascending
                            ? students.OrderBy(s => s.LastName).ToList()
                            : students.OrderByDescending(s => s.LastName).ToList();
                        break;
                    case SortOption.Group:
                        sortedList = sortDirection == SortDirection.Ascending
                            ? students.OrderBy(s => s.Group).ToList()
                            : students.OrderByDescending(s => s.Group).ToList();
                        break;
                    case SortOption.Course:
                        sortedList = sortDirection == SortDirection.Ascending
                            ? students.OrderBy(s => s.Course).ToList()
                            : students.OrderByDescending(s => s.Course).ToList();
                        break;
                    case SortOption.BirthDate:
                        sortedList = sortDirection == SortDirection.Ascending
                            ? students.OrderBy(s => s.BirthDate).ToList()
                            : students.OrderByDescending(s => s.BirthDate).ToList();
                        break;
                }

                students = new BindingList<Student>(sortedList);
                dataGridView.DataSource = students;
                isModified = true;
            }
        }

        private void buttonFilter_Click(object sender, EventArgs e)
        {
            var filterForm = new FilterForm();
            if (filterForm.ShowDialog() == DialogResult.OK)
            {
                var filtered = students.AsQueryable();

                if (filterForm.Course.HasValue)
                {
                    filtered = filtered.Where(s => s.Course == filterForm.Course.Value);
                }

                if (!string.IsNullOrWhiteSpace(filterForm.Group))
                {
                    filtered = filtered.Where(s => s.Group.Contains(filterForm.Group));
                }

                dataGridView.DataSource = new BindingList<Student>(filtered.ToList());
            }
            else
            {
                dataGridView.DataSource = students;
            }
        }

        private void buttonSearch_Click(object sender, EventArgs e)
        {
            var searchForm = new SearchForm();
            if (searchForm.ShowDialog() == DialogResult.OK)
            {
                var searchText = searchForm.SearchText.ToLower();
                var searchOption = searchForm.SearchOption;

                var filtered = students.Where(s =>
                    (searchOption == SearchOption.LastName && s.LastName.ToLower().Contains(searchText)) ||
                    (searchOption == SearchOption.FirstName && s.FirstName.ToLower().Contains(searchText)) ||
                    (searchOption == SearchOption.Both &&
                     (s.LastName.ToLower().Contains(searchText) || s.FirstName.ToLower().Contains(searchText)))
                ).ToList();

                dataGridView.DataSource = new BindingList<Student>(filtered);
            }
            else
            {
                dataGridView.DataSource = students;
            }
        }

        private void buttonStats_Click(object sender, EventArgs e)
        {
            var statsForm = new StatsForm(students.ToList());
            statsForm.ShowDialog();
        }

        private void сохранитьToolStripMenuItem_Click(object sender, EventArgs e)
        {
            SaveFile();
        }

        private void SaveFile()
        {
            if (string.IsNullOrEmpty(currentFilePath))
            {
                SaveFileAs();
            }
            else
            {
                SaveToFile(currentFilePath);
            }
        }

        private void SaveFileAs()
        {
            saveFileDialog.Filter = "JSON files (*.json)|*.json";
            if (saveFileDialog.ShowDialog() == DialogResult.OK)
            {
                currentFilePath = saveFileDialog.FileName;
                SaveToFile(currentFilePath);
            }
        }

        private void SaveToFile(string filePath)
        {
            try
            {
                var json = JsonConvert.SerializeObject(students.ToList(), Formatting.Indented);
                File.WriteAllText(filePath, json);
                isModified = false;
                MessageBox.Show("Данные успешно сохранены", "Сохранение", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при сохранении файла: {ex.Message}", "Ошибка", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void открытьToolStripMenuItem_Click(object sender, EventArgs e)
        {
            if (isModified)
            {
                var result = MessageBox.Show("Есть несохраненные изменения. Сохранить перед открытием нового файла?",
                    "Подтверждение", MessageBoxButtons.YesNoCancel, MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    SaveFile();
                }
                else if (result == DialogResult.Cancel)
                {
                    return;
                }
            }

            openFileDialog.Filter = "JSON files (*.json)|*.json";
            if (openFileDialog.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    var json = File.ReadAllText(openFileDialog.FileName);
                    var loadedStudents = JsonConvert.DeserializeObject<List<Student>>(json);

                    students.Clear();
                    foreach (var student in loadedStudents)
                    {
                        students.Add(student);
                    }

                    currentFilePath = openFileDialog.FileName;
                    isModified = false;
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Ошибка при загрузке файла: {ex.Message}", "Ошибка", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void новыйToolStripMenuItem_Click(object sender, EventArgs e)
        {
            if (isModified)
            {
                var result = MessageBox.Show("Есть несохраненные изменения. Сохранить перед созданием нового файла?",
                    "Подтверждение", MessageBoxButtons.YesNoCancel, MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    SaveFile();
                }
                else if (result == DialogResult.Cancel)
                {
                    return;
                }
            }

            students.Clear();
            currentFilePath = string.Empty;
            isModified = false;
            ClearForm();
        }

        private void импортCSVToolStripMenuItem_Click(object sender, EventArgs e)
        {
            openFileDialog.Filter = "CSV files (*.csv)|*.csv";
            if (openFileDialog.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    var lines = File.ReadAllLines(openFileDialog.FileName);
                    var importedStudents = new List<Student>();

                    foreach (var line in lines.Skip(1)) // Пропускаем заголовок
                    {
                        var values = line.Split(',');
                        if (values.Length >= 8)
                        {
                            var student = new Student
                            {
                                LastName = values[0],
                                FirstName = values[1],
                                MiddleName = values[2],
                                Course = int.Parse(values[3]),
                                Group = values[4],
                                BirthDate = DateTime.Parse(values[5]),
                                Email = values[6],
                                Phone = values[7]
                            };

                            if (IsValidEmail(student.Email) && IsValidPhone(student.Phone))
                            {
                                importedStudents.Add(student);
                            }
                        }
                    }

                    students.Clear();
                    foreach (var student in importedStudents)
                    {
                        students.Add(student);
                    }

                    isModified = true;
                    MessageBox.Show($"Успешно импортировано {importedStudents.Count} студентов", "Импорт", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Ошибка при импорте CSV: {ex.Message}", "Ошибка", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void экспортCSVToolStripMenuItem_Click(object sender, EventArgs e)
        {
            saveFileDialog.Filter = "CSV files (*.csv)|*.csv";
            if (saveFileDialog.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    var lines = new List<string>();
                    lines.Add("Фамилия,Имя,Отчество,Курс,Группа,Дата рождения,Email,Телефон");

                    foreach (var student in students)
                    {
                        lines.Add($"{student.LastName},{student.FirstName},{student.MiddleName}," +
                                 $"{student.Course},{student.Group},{student.BirthDate.ToShortDateString()}," +
                                 $"{student.Email},{student.Phone}");
                    }

                    File.WriteAllLines(saveFileDialog.FileName, lines);
                    MessageBox.Show("Данные успешно экспортированы в CSV", "Экспорт", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Ошибка при экспорте CSV: {ex.Message}", "Ошибка", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void оПрограммеToolStripMenuItem_Click(object sender, EventArgs e)
        {
            MessageBox.Show("Приложение для управления информацией о студентах\n\nВерсия 1.0", "О программе", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void выходToolStripMenuItem_Click(object sender, EventArgs e)
        {
            Close();
        }

        private void MainForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            if (isModified)
            {
                var result = MessageBox.Show("Есть несохраненные изменения. Сохранить перед выходом?",
                    "Подтверждение", MessageBoxButtons.YesNoCancel, MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    SaveFile();
                }
                else if (result == DialogResult.Cancel)
                {
                    e.Cancel = true;
                }
            }
        }
    }
}