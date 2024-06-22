# Code Challenge:
1. To run  the code: After running PHP server on your PC and downloading the project folder, please run the URL 
http://localhost/when_i_work/calculate_shifts.php on your web browser to see the output.
The input json file "dataset.json" is saved on the folder  "input_handler".

2. I have done the unit testing and checked the following test cases:
   - the regular hours and overtime hours of the employees in given week
   - The case where a shift crosses midnight of Sunday
   - Invalid shifts 
   - Week that transitions from CST to CDT and vice versa
   - Other input and output validations

3. If I spent more time on this task I would do the following enhancements:
   - I would store the CT timezone as constant field on a file named const.php  and access that from different files
   - After processing a input json file I would rename the file with timestamp so that we could pick next input file for processing
   - I would implement the function updateHoursOfTheWeek in a different way
