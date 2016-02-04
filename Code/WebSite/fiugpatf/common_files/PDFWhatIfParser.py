import PyPDF2, re, sys
from sets import Set

def getBucketTrueName(PDFName):
	if PDFName == "First Year Experience":
		return "First Year Experience"
	elif PDFName == "d English Composition":
		return "Communication"
	elif PDFName == "Humanities - Group One":
		return "Humanities - Group 1"
	elif PDFName == "Humanities - Group Two":
		return "Humanities - Group 2"
	elif PDFName == "Mathematics Group One":
		return "Mathematics - Group 1"
	elif PDFName == "Mathematics Group Two":
		return "Mathematics - Group 2"
	elif PDFName == "Social Science Group One":
		return "Social Sciences - Group 1"
	elif PDFName == "Social Science Group Two":
		return "Social Sciences - Group 2"
	elif PDFName == "NATURAL SCIENCES - GROUP ONE":
		return "Natural Sciences - Group 1"
	elif PDFName == "NATURAL SCIENCE - GROUP TWO":
		return "Natural Sciences - Group 2"
	elif PDFName == "Arts":
		return "Arts"
	elif PDFName == "Prerequisites":
		return "CS Prerequisites"
	elif PDFName == "Natural Science Additional":
		return "CS Science Elective Courses"
	elif PDFName == "Core Courses":
		return "CS Core Courses"
	elif PDFName == "Electives":
		return "CS Science Elective Courses"
	elif PDFName == "Mathematics and Sciences":
		return "Mathematics and Sciences"
	elif PDFName == "CAD requirement":
		return "CAD requirement"
	elif PDFName == "Engineering Orientation  ":
		return "Engineering Orientation"
	elif PDFName == "Additional Required Engineering Breadth":
		return "Additional Required Engineering Bre"
	elif PDFName == "ECE Courses":
		return "ECE Courses"
	elif PDFName == "Senior Design":
		return "Senior Design"
	elif PDFName == "Electrical Engineering (EE) Program Core":
		return "Electrical Engineering (EE) Program Core"
	elif PDFName == "Power / Energy":
		return "Power / Energy"
	elif PDFName == "Control Systems":
		return "Control Systems"	
	elif PDFName == "Integrated Nano-technology":
		return "Integrated Nano-technology"
	elif PDFName == "Communications":
		return "Communications"
	elif PDFName == "Embedded Systems":
		return "Embedded Systems"
	elif PDFName == "Bio-Engineering":
		return "Bio-Engineering"
	elif PDFName == "Computer Architecture & Microprocessor Design":
		return "Computer Architecture and Microprocessor Design"
	elif PDFName == "Data System Software":
		return "Data System Software"
	else:
		return "None"

def splitMajor(maj):
	temp = maj[0]
	for i in range (1, len(maj)):
		if maj[i].isupper():
			temp = temp + " " + maj[i]
		else:
			temp = temp + maj[i]
	return temp
	
	
	
parseFile = ''.join(sys.argv[1])
whatIfPDF = open(parseFile, 'rb')
WhatIfReader = PyPDF2.PdfFileReader(whatIfPDF)
allCourses = set([])
allText = ""

for i in range (0, WhatIfReader.numPages):
	page = WhatIfReader.getPage(i)
	allText = allText + page.extractText()

#Gets major
major = ""
majors = re.findall(r"\d\d\d\d(.{1,30})-BSMajor", allText)
for x in range (0, len(majors)):
	print splitMajor(majors[x])

#For parsing in the php file
print "!!!!"

#Gets courses already taken.
courses = re.findall(r"(First Year Experience|d English Composition|Humanities - Group One|Humanities - Group Two|Mathematics Group One|Mathematics Group Two|NATURAL SCIENCES - GROUP ONE|NATURAL SCIENCE - GROUP TWO|Social Science Group One|Social Science Group Two|Arts|Prerequisites|Natural Science Additional|Core Courses|Electives|Mathematics and Sciences|CAD requirement|Engineering Orientation|Additional Required Engineering Breadth|ECE Courses|Senior Design|Electrical Engineering \(EE\) Program Core|Power / Energy|Control Systems|Integrated Nano-technology|Communications|Embedded Systems|Bio-Engineering|Computer Architecture & Microprocessor Design|Data System Software|Additional Courses).*?(UsedTermSubjectCatalog NbrCourse TitleGradeUnitsTypeRepeat|Select From:)(.*?)(\s\s)",allText)
for y in range (0, len(courses)):
	bucket = getBucketTrueName(courses[y][0])
	courseBreakdown = re.findall(r"(FALL|SPR|SUM)\s(\d\d\d\d)([A-Z][A-Z][A-Z]\d\d\d\d)(.*?)(A|A-|B|B[+-]|C|C[+-]|D|D[+-]|F|P|DR|TR)(\d+[.]\d\d)", courses[y][2])
	for z in range (0, len(courseBreakdown)):
		if courseBreakdown:
			semester = courseBreakdown[z][0]
			if semester == "FALL":
				semester = "Fall"
			elif semester == "SPR":
				semester = "Spring"
			elif semester == "SUM":
				semester = "Summer"
			year = courseBreakdown[z][1]
			course = courseBreakdown[z][2]
			courseDescription = courseBreakdown[z][3]
				
			if courseDescription[0] == "L" and  not courseDescription.istitle():
				course = course + "L"
				courseDescription = courseDescription[1:]
					
			grade = courseBreakdown[z][4]
			credits = courseBreakdown[z][5]
			print bucket + "$$&&" + semester + "$$&&"+ year + "$$&&" + course + "$$&&" +courseDescription + "$$&&" + grade + "$$&&" + credits 

print "!!!!"
			
#Get classes in Progress
for a in range (0, len(courses)):
	bucket = getBucketTrueName(courses[a][0])
	courseBreakdown = re.findall(r"(FALL|SPR|SUM)\s(\d\d\d\d)([A-Z][A-Z][A-Z]\d\d\d\d)(.{1,20})(\d+[.]\d\d)IP", courses[a][2])
	for b in range (0, len(courseBreakdown)):
		if courseBreakdown:
			semester = courseBreakdown[b][0]
			if semester == "FALL":
				semester = "Fall"
			elif semester == "SPR":
				semester = "Spring"
			elif semester == "SUM":
				semester = "Summer"
			year = courseBreakdown[b][1]
			course = courseBreakdown[b][2]
			courseDescription = courseBreakdown[b][3]
				
			if courseDescription[0] == "L" and  not courseDescription.istitle():
				course = course + "L"
				courseDescription = courseDescription[1:]
					
			grade = "IP"
			credits = courseBreakdown[b][4]
			print bucket + "$$&&" + semester + "$$&&"+ year + "$$&&" + course + "$$&&" +courseDescription + "$$&&" + grade + "$$&&" + credits
			
print "!!!!"

#get needed courses
neededCourses = re.findall(r"Select From:\s\s\s\s\s(.*?)\s\s", allText)
for c in range (0, len(neededCourses)):
	course = re.findall(r"([A-Z][A-Z][A-Z])\s(\d\d\d\d)",neededCourses[c])
	for d in range (0, len(course)):
		print course[d][0] + course[d][1]
