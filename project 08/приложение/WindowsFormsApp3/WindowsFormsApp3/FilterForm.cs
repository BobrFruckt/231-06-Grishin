using System;
using System.Windows.Forms;

namespace StudentsApp
{
    public partial class FilterForm : Form
    {
        private NumericUpDown numericUpDownCourse;
        private TextBox textBoxGroup;
        private CheckBox checkBoxCourse;
        private CheckBox checkBoxGroup;
        private Button buttonOK;
        private Button buttonCancel;

        public int? Course { get; private set; }
        public string Group { get; private set; }

        public FilterForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.numericUpDownCourse = new System.Windows.Forms.NumericUpDown();
            this.textBoxGroup = new System.Windows.Forms.TextBox();
            this.checkBoxCourse = new System.Windows.Forms.CheckBox();
            this.checkBoxGroup = new System.Windows.Forms.CheckBox();
            this.buttonOK = new System.Windows.Forms.Button();
            this.buttonCancel = new System.Windows.Forms.Button();
            ((System.ComponentModel.ISupportInitialize)(this.numericUpDownCourse)).BeginInit();
            this.SuspendLayout();

            // numericUpDownCourse
            this.numericUpDownCourse.Location = new System.Drawing.Point(120, 15);
            this.numericUpDownCourse.Minimum = new decimal(new int[] {
            1,
            0,
            0,
            0});
            this.numericUpDownCourse.Maximum = new decimal(new int[] {
            6,
            0,
            0,
            0});
            this.numericUpDownCourse.Name = "numericUpDownCourse";
            this.numericUpDownCourse.Size = new System.Drawing.Size(120, 20);
            this.numericUpDownCourse.TabIndex = 0;
            this.numericUpDownCourse.Enabled = false;

            // textBoxGroup
            this.textBoxGroup.Location = new System.Drawing.Point(120, 45);
            this.textBoxGroup.Name = "textBoxGroup";
            this.textBoxGroup.Size = new System.Drawing.Size(120, 20);
            this.textBoxGroup.TabIndex = 1;
            this.textBoxGroup.Enabled = false;

            // checkBoxCourse
            this.checkBoxCourse.AutoSize = true;
            this.checkBoxCourse.Location = new System.Drawing.Point(15, 16);
            this.checkBoxCourse.Name = "checkBoxCourse";
            this.checkBoxCourse.Size = new System.Drawing.Size(99, 17);
            this.checkBoxCourse.TabIndex = 2;
            this.checkBoxCourse.Text = "Фильтр по курсу";
            this.checkBoxCourse.UseVisualStyleBackColor = true;
            this.checkBoxCourse.CheckedChanged += new System.EventHandler(this.checkBoxCourse_CheckedChanged);

            // checkBoxGroup
            this.checkBoxGroup.AutoSize = true;
            this.checkBoxGroup.Location = new System.Drawing.Point(15, 47);
            this.checkBoxGroup.Name = "checkBoxGroup";
            this.checkBoxGroup.Size = new System.Drawing.Size(105, 17);
            this.checkBoxGroup.TabIndex = 3;
            this.checkBoxGroup.Text = "Фильтр по группе";
            this.checkBoxGroup.UseVisualStyleBackColor = true;
            this.checkBoxGroup.CheckedChanged += new System.EventHandler(this.checkBoxGroup_CheckedChanged);

            // buttonOK
            this.buttonOK.Location = new System.Drawing.Point(50, 80);
            this.buttonOK.Name = "buttonOK";
            this.buttonOK.Size = new System.Drawing.Size(75, 23);
            this.buttonOK.TabIndex = 4;
            this.buttonOK.Text = "Применить";
            this.buttonOK.UseVisualStyleBackColor = true;
            this.buttonOK.Click += new System.EventHandler(this.buttonOK_Click);

            // buttonCancel
            this.buttonCancel.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.buttonCancel.Location = new System.Drawing.Point(150, 80);
            this.buttonCancel.Name = "buttonCancel";
            this.buttonCancel.Size = new System.Drawing.Size(75, 23);
            this.buttonCancel.TabIndex = 5;
            this.buttonCancel.Text = "Отмена";
            this.buttonCancel.UseVisualStyleBackColor = true;
            this.buttonCancel.Click += new System.EventHandler(this.buttonCancel_Click);

            // FilterForm
            this.AcceptButton = this.buttonOK;
            this.CancelButton = this.buttonCancel;
            this.ClientSize = new System.Drawing.Size(260, 120);
            this.Controls.Add(this.buttonCancel);
            this.Controls.Add(this.buttonOK);
            this.Controls.Add(this.checkBoxGroup);
            this.Controls.Add(this.checkBoxCourse);
            this.Controls.Add(this.textBoxGroup);
            this.Controls.Add(this.numericUpDownCourse);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "FilterForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Фильтрация студентов";
            ((System.ComponentModel.ISupportInitialize)(this.numericUpDownCourse)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        private void buttonOK_Click(object sender, EventArgs e)
        {
            if (checkBoxCourse.Checked)
            {
                Course = (int)numericUpDownCourse.Value;
            }
            else
            {
                Course = null;
            }

            if (checkBoxGroup.Checked)
            {
                Group = textBoxGroup.Text;
            }
            else
            {
                Group = null;
            }

            DialogResult = DialogResult.OK;
            Close();
        }

        private void buttonCancel_Click(object sender, EventArgs e)
        {
            DialogResult = DialogResult.Cancel;
            Close();
        }

        private void checkBoxCourse_CheckedChanged(object sender, EventArgs e)
        {
            numericUpDownCourse.Enabled = checkBoxCourse.Checked;
        }

        private void checkBoxGroup_CheckedChanged(object sender, EventArgs e)
        {
            textBoxGroup.Enabled = checkBoxGroup.Checked;
        }
    }
}