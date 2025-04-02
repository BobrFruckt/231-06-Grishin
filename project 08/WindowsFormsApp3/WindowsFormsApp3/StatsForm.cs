using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows.Forms;

namespace StudentsApp
{
    public partial class StatsForm : Form
    {
        private ListBox listBoxStats;
        private Button buttonClose;

        public StatsForm(List<Student> students)
        {
            InitializeComponent();
            LoadStats(students);
        }

        private void InitializeComponent()
        {
            this.listBoxStats = new System.Windows.Forms.ListBox();
            this.buttonClose = new System.Windows.Forms.Button();
            this.SuspendLayout();
            // 
            // listBoxStats
            // 
            this.listBoxStats.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.listBoxStats.FormattingEnabled = true;
            this.listBoxStats.Location = new System.Drawing.Point(12, 12);
            this.listBoxStats.Name = "listBoxStats";
            this.listBoxStats.Size = new System.Drawing.Size(360, 199);
            this.listBoxStats.TabIndex = 0;
            // 
            // buttonClose
            // 
            this.buttonClose.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.buttonClose.Location = new System.Drawing.Point(297, 217);
            this.buttonClose.Name = "buttonClose";
            this.buttonClose.Size = new System.Drawing.Size(75, 23);
            this.buttonClose.TabIndex = 1;
            this.buttonClose.Text = "Закрыть";
            this.buttonClose.UseVisualStyleBackColor = true;
            this.buttonClose.Click += new System.EventHandler(this.buttonClose_Click);
            // 
            // StatsForm
            // 
            this.ClientSize = new System.Drawing.Size(384, 252);
            this.Controls.Add(this.buttonClose);
            this.Controls.Add(this.listBoxStats);
            this.MinimumSize = new System.Drawing.Size(400, 290);
            this.Name = "StatsForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Статистика студентов";
            this.ResumeLayout(false);
        }

        private void LoadStats(List<Student> students)
        {
            // Статистика по курсам
            var courseStats = students
                .GroupBy(s => s.Course)
                .OrderBy(g => g.Key)
                .Select(g => new { Course = g.Key, Count = g.Count() });

            foreach (var stat in courseStats)
            {
                listBoxStats.Items.Add($"Курс {stat.Course}: {stat.Count} студентов");
            }

            listBoxStats.Items.Add("");

            // Статистика по группам
            var groupStats = students
                .GroupBy(s => s.Group)
                .OrderBy(g => g.Key)
                .Select(g => new { Group = g.Key, Count = g.Count() });

            foreach (var stat in groupStats)
            {
                listBoxStats.Items.Add($"Группа {stat.Group}: {stat.Count} студентов");
            }

            listBoxStats.Items.Add("");
            listBoxStats.Items.Add($"Всего студентов: {students.Count}");
        }

        private void buttonClose_Click(object sender, EventArgs e)
        {
            Close();
        }
    }
}