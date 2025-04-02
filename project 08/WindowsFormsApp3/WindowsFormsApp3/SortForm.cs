using System;
using System.Windows.Forms;

namespace StudentsApp
{
    public enum SortOption { LastName, Group, Course, BirthDate }
    public enum SortDirection { Ascending, Descending }

    public partial class SortForm : Form
    {
        private ComboBox comboBoxSortField;
        private RadioButton radioButtonAsc;
        private RadioButton radioButtonDesc;
        private Button buttonOK;
        private Button buttonCancel;

        public SortOption SelectedSortOption { get; private set; }
        public SortDirection SortDirection { get; private set; }

        public SortForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.comboBoxSortField = new System.Windows.Forms.ComboBox();
            this.radioButtonAsc = new System.Windows.Forms.RadioButton();
            this.radioButtonDesc = new System.Windows.Forms.RadioButton();
            this.buttonOK = new System.Windows.Forms.Button();
            this.buttonCancel = new System.Windows.Forms.Button();
            this.SuspendLayout();
            // 
            // comboBoxSortField
            // 
            this.comboBoxSortField.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.comboBoxSortField.FormattingEnabled = true;
            this.comboBoxSortField.Location = new System.Drawing.Point(12, 12);
            this.comboBoxSortField.Name = "comboBoxSortField";
            this.comboBoxSortField.Size = new System.Drawing.Size(200, 21);
            this.comboBoxSortField.TabIndex = 0;
            // 
            // radioButtonAsc
            // 
            this.radioButtonAsc.AutoSize = true;
            this.radioButtonAsc.Checked = true;
            this.radioButtonAsc.Location = new System.Drawing.Point(12, 45);
            this.radioButtonAsc.Name = "radioButtonAsc";
            this.radioButtonAsc.Size = new System.Drawing.Size(118, 17);
            this.radioButtonAsc.TabIndex = 1;
            this.radioButtonAsc.TabStop = true;
            this.radioButtonAsc.Text = "По возрастанию";
            this.radioButtonAsc.UseVisualStyleBackColor = true;
            // 
            // radioButtonDesc
            // 
            this.radioButtonDesc.AutoSize = true;
            this.radioButtonDesc.Location = new System.Drawing.Point(136, 45);
            this.radioButtonDesc.Name = "radioButtonDesc";
            this.radioButtonDesc.Size = new System.Drawing.Size(104, 17);
            this.radioButtonDesc.TabIndex = 2;
            this.radioButtonDesc.Text = "По убыванию";
            this.radioButtonDesc.UseVisualStyleBackColor = true;
            // 
            // buttonOK
            // 
            this.buttonOK.Location = new System.Drawing.Point(56, 80);
            this.buttonOK.Name = "buttonOK";
            this.buttonOK.Size = new System.Drawing.Size(75, 23);
            this.buttonOK.TabIndex = 3;
            this.buttonOK.Text = "OK";
            this.buttonOK.UseVisualStyleBackColor = true;
            this.buttonOK.Click += new System.EventHandler(this.buttonOK_Click);
            // 
            // buttonCancel
            // 
            this.buttonCancel.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.buttonCancel.Location = new System.Drawing.Point(137, 80);
            this.buttonCancel.Name = "buttonCancel";
            this.buttonCancel.Size = new System.Drawing.Size(75, 23);
            this.buttonCancel.TabIndex = 4;
            this.buttonCancel.Text = "Отмена";
            this.buttonCancel.UseVisualStyleBackColor = true;
            this.buttonCancel.Click += new System.EventHandler(this.buttonCancel_Click);
            // 
            // SortForm
            // 
            this.AcceptButton = this.buttonOK;
            this.CancelButton = this.buttonCancel;
            this.ClientSize = new System.Drawing.Size(224, 115);
            this.Controls.Add(this.buttonCancel);
            this.Controls.Add(this.buttonOK);
            this.Controls.Add(this.radioButtonDesc);
            this.Controls.Add(this.radioButtonAsc);
            this.Controls.Add(this.comboBoxSortField);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "SortForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Сортировка";
            this.Load += new System.EventHandler(this.SortForm_Load);
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        private void buttonOK_Click(object sender, EventArgs e)
        {
            SelectedSortOption = (SortOption)comboBoxSortField.SelectedItem;
            SortDirection = radioButtonAsc.Checked ? SortDirection.Ascending : SortDirection.Descending;
            DialogResult = DialogResult.OK;
            Close();
        }

        private void buttonCancel_Click(object sender, EventArgs e)
        {
            DialogResult = DialogResult.Cancel;
            Close();
        }

        private void SortForm_Load(object sender, EventArgs e)
        {
            comboBoxSortField.DataSource = Enum.GetValues(typeof(SortOption));
            comboBoxSortField.SelectedIndex = 0;
        }
    }
}