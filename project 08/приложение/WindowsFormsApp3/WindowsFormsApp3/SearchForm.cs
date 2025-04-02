using System;
using System.Windows.Forms;

namespace StudentsApp
{
    public enum SearchOption { LastName, FirstName, Both }

    public partial class SearchForm : Form
    {
        private ComboBox comboBoxSearchField;
        private TextBox textBoxSearch;
        private Button buttonOK;
        private Button buttonCancel;
        private Label label1;
        private Label label2;

        public string SearchText { get; private set; }
        public SearchOption SearchOption { get; private set; }

        public SearchForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.comboBoxSearchField = new System.Windows.Forms.ComboBox();
            this.textBoxSearch = new System.Windows.Forms.TextBox();
            this.buttonOK = new System.Windows.Forms.Button();
            this.buttonCancel = new System.Windows.Forms.Button();
            this.label1 = new System.Windows.Forms.Label();
            this.label2 = new System.Windows.Forms.Label();
            this.SuspendLayout();

            // comboBoxSearchField
            this.comboBoxSearchField.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.comboBoxSearchField.FormattingEnabled = true;
            this.comboBoxSearchField.Location = new System.Drawing.Point(120, 15);
            this.comboBoxSearchField.Name = "comboBoxSearchField";
            this.comboBoxSearchField.Size = new System.Drawing.Size(150, 21);
            this.comboBoxSearchField.TabIndex = 0;

            // textBoxSearch
            this.textBoxSearch.Location = new System.Drawing.Point(120, 45);
            this.textBoxSearch.Name = "textBoxSearch";
            this.textBoxSearch.Size = new System.Drawing.Size(150, 20);
            this.textBoxSearch.TabIndex = 1;

            // buttonOK
            this.buttonOK.Location = new System.Drawing.Point(50, 80);
            this.buttonOK.Name = "buttonOK";
            this.buttonOK.Size = new System.Drawing.Size(75, 23);
            this.buttonOK.TabIndex = 2;
            this.buttonOK.Text = "Поиск";
            this.buttonOK.UseVisualStyleBackColor = true;
            this.buttonOK.Click += new System.EventHandler(this.buttonOK_Click);

            // buttonCancel
            this.buttonCancel.DialogResult = System.Windows.Forms.DialogResult.Cancel;
            this.buttonCancel.Location = new System.Drawing.Point(150, 80);
            this.buttonCancel.Name = "buttonCancel";
            this.buttonCancel.Size = new System.Drawing.Size(75, 23);
            this.buttonCancel.TabIndex = 3;
            this.buttonCancel.Text = "Отмена";
            this.buttonCancel.UseVisualStyleBackColor = true;
            this.buttonCancel.Click += new System.EventHandler(this.buttonCancel_Click);

            // label1
            this.label1.AutoSize = true;
            this.label1.Location = new System.Drawing.Point(15, 18);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(99, 13);
            this.label1.TabIndex = 4;
            this.label1.Text = "Искать по полю:";

            // label2
            this.label2.AutoSize = true;
            this.label2.Location = new System.Drawing.Point(15, 48);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(105, 13);
            this.label2.TabIndex = 5;
            this.label2.Text = "Введите значение:";

            // SearchForm
            this.AcceptButton = this.buttonOK;
            this.CancelButton = this.buttonCancel;
            this.ClientSize = new System.Drawing.Size(284, 121);
            this.Controls.Add(this.label2);
            this.Controls.Add(this.label1);
            this.Controls.Add(this.buttonCancel);
            this.Controls.Add(this.buttonOK);
            this.Controls.Add(this.textBoxSearch);
            this.Controls.Add(this.comboBoxSearchField);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "SearchForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Поиск студента";
            this.Load += new System.EventHandler(this.SearchForm_Load);
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        private void SearchForm_Load(object sender, EventArgs e)
        {
            comboBoxSearchField.DataSource = Enum.GetValues(typeof(SearchOption));
            comboBoxSearchField.SelectedIndex = 0;
        }

        private void buttonOK_Click(object sender, EventArgs e)
        {
            SearchText = textBoxSearch.Text;
            SearchOption = (SearchOption)comboBoxSearchField.SelectedItem;
            DialogResult = DialogResult.OK;
            Close();
        }

        private void buttonCancel_Click(object sender, EventArgs e)
        {
            DialogResult = DialogResult.Cancel;
            Close();
        }
    }
}