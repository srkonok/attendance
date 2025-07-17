import random

def generate_attendance(total_classes=42, present_percentage=70):
    present_count = round((present_percentage / 100) * total_classes)
    absent_count = total_classes - present_count

    attendance = ['P'] * present_count + ['A'] * absent_count
    random.shuffle(attendance)

    return attendance

def main():
    try:
        percentage = float(input("Enter attendance percentage (e.g., 73.68): "))
        if not 0 <= percentage <= 100:
            print("Please enter a percentage between 0 and 100.")
            return

        attendance_list = generate_attendance(present_percentage=percentage)

        print("\nGenerated Attendance List:")
        print(" ".join(attendance_list))
        print(f"Total Present: {attendance_list.count('P')}")
        print(f"Total Absent : {attendance_list.count('A')}")

    except ValueError:
        print("Invalid input. Please enter a numeric percentage.")

if __name__ == "__main__":
    main()
