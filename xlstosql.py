import pandas as pd
import re

# Read Excel with header at row index 3
df = pd.read_excel("Section B(4.2).xlsx", header=3)

# Clean column names
df.columns = df.columns.str.strip().str.upper()

print("🧾 Columns Detected:", df.columns.tolist())

# Rename contact column if needed
if 'CONTACT NO' in df.columns:
    df.rename(columns={'CONTACT NO': 'PHONE'}, inplace=True)

# Ensure required columns
required_columns = ['ID', 'NAME', 'EMAIL']
for col in required_columns:
    if col not in df.columns:
        raise Exception(f"❌ Required column '{col}' not found.")

# Handle phone column (convert to int)
if 'PHONE' in df.columns:
    def clean_phone(p):
        if pd.isnull(p):
            return 'NULL'
        # Remove non-digit characters
        cleaned = re.sub(r'\D', '', str(p))
        return cleaned if cleaned else 'NULL'

    df['PHONE_INT'] = df['PHONE'].apply(clean_phone)
else:
    df['PHONE_INT'] = 'NULL'
    print("⚠️ No phone column found. All phone numbers will be NULL.")

# Generate SQL INSERTs
insert_statements = []
for _, row in df.iterrows():
    student_id = str(row['ID']).strip()
    name = str(row['NAME']).replace("'", "''").strip()
    email = str(row['EMAIL']).replace("'", "''").strip()
    phone = row['PHONE_INT']
    phone_value = phone if phone == 'NULL' else f"{phone}"
    section = 'B'
    insert_statements.append(
        f"('{student_id}', '{name}', {phone_value}, '{email}', '{section}')"
    )

# Final SQL script
sql_script = (
    "INSERT INTO students (student_id, name, phone_number, email, section) VALUES\n"
    + ",\n".join(insert_statements)
    + ";"
)

# Save to file
with open("insert_students_section_B.sql", "w", encoding="utf-8") as f:
    f.write(sql_script)

print("✅ SQL file saved as insert_students_section_B.sql (phone as int)")
