===============================================
INC EMAIL SYSTEM - SETUP AT PAGGAMIT
===============================================

PROBLEMA NA NASOLUSYUNAN:
1. Hindi nag-eemail sa students na may INC
2. Walang email notification pag nag-set ng exam schedule

SOLUSYON:

STEP 1: I-UPDATE ANG DATABASE
------------------------------
Run ang SQL file sa phpMyAdmin:
- File: add_exam_details_columns.sql
- Ito ay magdadagdag ng exam_date, exam_time, exam_venue, exam_details_sent columns sa academic_alerts table

STEP 2: I-SEND ANG INC EMAILS SA LAHAT NG STUDENTS
---------------------------------------------------
Run sa browser: http://localhost/citizenz/send_inc_emails.php
- Ito ay mag-sesend ng email sa lahat ng students na may finals_Exam = 0
- Automatic na gagawa ng INC alerts sa academic_alerts table
- Mag-sesend ng notifications

STEP 3: PAANO MAG-SET NG EXAM SCHEDULE (PARA SA TEACHER)
---------------------------------------------------------
1. Login bilang teacher
2. Pumunta sa "Students with INC Status" page
3. Click ang "Set Exam Schedule" button (green button sa taas)
4. Makikita mo lahat ng students na may INC sa iyong subjects
5. Click "Set Schedule" button sa bawat student
6. Fill up ang form:
   - Exam Date
   - Exam Time
   - Exam Venue
7. Click "Send to Student"
8. AUTOMATIC NA MAG-EEMAIL SA STUDENT ang exam details!

AUTOMATIC EMAIL FEATURES:
-------------------------
1. Pag may bagong INC (finals_Exam = 0):
   - Automatic email sa student
   - Notification sa dashboard
   - Entry sa academic_alerts table

2. Pag nag-set ng exam schedule ang teacher:
   - Automatic email sa student with exam details
   - Notification sa student
   - Update sa academic_alerts (INC -> EXAM status)

FILES NA GINAWA:
----------------
1. add_exam_details_columns.sql - Database migration
2. teacher_set_exam_schedule.php - Teacher interface para mag-set ng exam
3. send_inc_emails.php - Script para mag-send ng INC emails sa lahat
4. teacher_inc_students.php - Updated with "Set Exam Schedule" button

EXISTING FILES NA MAY EMAIL:
----------------------------
- dashboard.php (lines 346-395) - Auto-detect INC at mag-send ng email

IMPORTANTE:
-----------
- Siguradong naka-run na ang add_exam_details_columns.sql bago gamitin ang system
- Email credentials ay naka-configure na (yloludovice709@gmail.com)
- Lahat ng emails ay automatic na, walang manual sending needed
