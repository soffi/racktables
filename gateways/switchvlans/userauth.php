<?
echo "Unauthorized access attempt has been logged. Cheers.";
exit();
# S-T-A-R-T
deny intruder@test connect
allow *@test connect
allow admin@core* connect
# temp entry
allow admin@switch1 connect
allow admin@switch2 connect
deny *@s* connect
allow username@endpoint change 1 2
allow username@* change 999 *
allow username@* change * 999
allow *@s* change * 999
allow *@s1 change 400 999
allow *@s2 change * 999
deny *@* change * *
# S-T-O-P
?>
