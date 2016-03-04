import PyPDF2, re, sys
from sets import Set

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
	print (splitMajor(majors[x]))

#For parsing in the php file
print ("!!!!")

#Gets courses already taken.
courses = re.findall(r"(Course History)(.*)",allText)

courseBreakdown = re.findall(r"(FALL|SPR|SUM)\s(\d\d\d\d)([A-Z][A-Z][A-Z]\d\d\d\d)(.*?)(A|A-|B|B[+-]|C|C[+-]|D|D[+-]|F|P|DR|TR)(\d+[.]\d\d)(TR|EN)", str(courses[0]))
for item in courseBreakdown:
	semester = item[0]
	if semester == "FALL":
		semester = "Fall"
	elif semester == "SPR":
		semester = "Spring"
	elif semester == "SUM":
		semester = "Summer"
	year = item[1]
	course = item[2]
	courseDescription = item[3]
	
	if courseDescription[0] == "L" and  not courseDescription.istitle():
		course = course + "L"
		courseDescription = courseDescription[1:]
		
	grade = item[4]
	credits = item[5]
	ctype = item[6]

	if ctype == 'EN':
		print semester + "$$&&"+ year + "$$&&" + course + "$$&&" +courseDescription + "$$&&" + grade + "$$&&" + credits 

#Get classes in Progress
inProgress = re.findall(r"(FALL|SPR|SUM)\s(\d\d\d\d)([A-Z][A-Z][A-Z]\d\d\d\d)(.{1,20})(\d+[.]\d\d)IP", str(courses[0]))
for item in inProgress:
	semester = item[0]
	if semester == "FALL":
		semester = "Fall"
	elif semester == "SPR":
		semester = "Spring"
	elif semester == "SUM":
		semester = "Summer"
	year = item[1]
	course = item[2]
	courseDescription = item[3]
		
	if courseDescription[0] == "L" and  not courseDescription.istitle():
		course = course + "L"
		courseDescription = courseDescription[1:]
			
	grade = "IP"
	credits = item[4]
	print semester + "$$&&"+ year + "$$&&" + course + "$$&&" +courseDescription + "$$&&" + grade + "$$&&" + credits

print ("!!!!")

#get needed courses
neededCourses = re.findall(r"Select From:\s\s\s\s\s(.*?)\s\s", allText)
for c in range (0, len(neededCourses)):
	course = re.findall(r"([A-Z][A-Z][A-Z])\s(\d\d\d\d)",neededCourses[c])
	for d in range (0, len(course)):
		print course[d][0] + course[d][1]








