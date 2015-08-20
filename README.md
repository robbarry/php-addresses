# php-addresses
An old class for normalizing addresses into little strings.

This class is designed to crush U.S. postal addresses into a standard format.
 
Make sure this file and address.idx.php are in the same directory.
  
## Examples

	require_once('address.class.php'); 		
	$addressParser = new AddressParser();

	echo $addressParser->parseAddress("123 Biltmore Boulevard #A-101") . "\n";
	//Output is: 123BILTMOREBLVD101A

	echo $addressParser->parseAddress("One Two Three Biltmore Boulv Unit 101A") . "\n";
	//Output is the same: 123BILTMOREBLVD101A
