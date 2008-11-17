<style type="text/css"><!--

    a.fontsizer  {
        margin-left:2px;
        padding:0 4px;
        text-decoration:none;
        font-weight:900;
        border-top:1px solid #ccf;
        border-left:1px solid #ccf;
        border-right:1px solid #99c;
        border-bottom:1px solid #99c;}
    a.fontsizer:hover { background:#f9f9f9;}

    .rfloat { float:right; margin-left:0.5em; }
    .lfloat { float:left; margin-right:0.5em; }
--></style>



<script type='text/javascript' language='JavaScript1.2'>
    var increment = 10;
    var cookiePrefix = 'triad3_triad3_';
    var fsLabel = 'Text Size';
    var fsBigger = 'bigger';
    var fsNormal = 'default';
    var fsSmaller = 'smaller';
</script>



<script type="text/javascript" language="JavaScript1.2">
    var rshow = '1';
    var lshow = '1';
    var show = 'Show';
    var hide = 'Hide';
</script>

<script type="text/javascript" language="JavaScript1.2">
function copacClick(lin,col,role)
{
//	alert(lin+" "+col+" "+role);
	var li = document.getElementById("copac_li_"+lin+"_"+col);
	var ul = document.getElementById("copac_ul_"+lin+"_"+col);
	if (!ul)
		return true;
	if (ul.style.display == "none") {
		ul.style.display = "block";
		li.setAttribute("type","circle");
		return true;
	}
	li.setAttribute("type","disc");
	for (c=col; ; c++) {
		ul = document.getElementById("copac_ul_"+lin+"_"+c);
		if (!ul)
			break;
		ul.style.display = "none";
	}
	return false;
}

function copacValoare(lin,col)
{
	var li = document.getElementById("copac_li_"+lin+"_"+col);
	if (!li)
		return null;
	var a = li.firstChild;
	if (!a)
		return null;
	return a.innerHTML;
}

function copacParinte(lin,col)
{
	if (col <= 0)
		return null;
	col--;
	for (; lin >= 0; lin--) {
		if (document.getElementById("copac_li_"+lin+"_"+col))
			return copacValoare(lin,col);
	}
	return null;
}

function copacComuta(nume)
{
	for (lin = 0; ; lin++) {
		var none = true;
		for (col = 0; col < 100; col++) {
			var li = document.getElementById("copac_li_"+lin+"_"+col);
			if (li) {
				none = false;
				if (nume == copacValoare(lin,col)) {
					copacClick(lin,col,"");
					return true;
				}
			}
		}
		if (none)
			return false;
	}
}

function open_invoice(url)
{
    win = window.open(url.href,url.target,"width=1000");
    if (win && window.focus)
	win.focus();
}

function show_hide(element)
{
	/*var div = document.getElementById("div_rates");
	var div_show = document.getElementById("show_r");
	var div_hide = document.getElementById("hide_r");
	if (div.style.display == "none") {
		div_show.style.display = "none";
		div_hide.style.display = "block";
		div.style.display = "block";
	}else{
		div_show.style.display = "block";
		div_hide.style.display = "none";
		div.style.display = "none";
	}*/
//alert(element);
	var div = document.getElementById(element + "_selected");
	if (div.style.display == "none") {
		div.style.display = "block";
	}else{
		div.style.display = "none";
	}
}

function form_for_gateway(gwtype)
{
	//var sprotocol = document.outbound.protocol.value;
	var sprotocol = document['forms']['outbound'][gwtype+'protocol'].value;
	var protocols = new Array("sip", "h323", "iax", "zap", "wp");
	var i;
	var currentdiv;
	var othergw;

	if(gwtype == "reg")
		othergw = "noreg";
	else
		othergw = "reg";

	for(var i=0; i<protocols.length; i++) 
	{
		currentdiv = document.getElementById("div_"+gwtype+"_"+protocols[i]);
		if(currentdiv == null)
			continue;
		if(currentdiv.style.display == "block")
			currentdiv.style.display = "none";
	}
	for(var i=0; i<protocols.length; i++) 
	{
		currentdiv = document.getElementById("div_"+othergw+"_"+protocols[i]);
		if(currentdiv == null)
			continue;
		if(currentdiv.style.display == "block")
			currentdiv.style.display = "none";
	}
	currentdiv = document.getElementById("div_"+gwtype+"_"+sprotocol);
	if(currentdiv == null)
		return false;
	if(currentdiv.style.display == "none")
		currentdiv.style.display = "block";
}

function show_hide_comment(id)
{
	var fontvr = document.getElementById("comment_"+id);
	if(fontvr == null)
		return;
	if (fontvr.style.display == "none")
		fontvr.style.display = "block";
	else
		if(fontvr.style.display == "block")
			fontvr.style.display = "none";
}

function form_for_dialplan(objname)
{
	var protocols = new Array("sip", "h323", "iax", "wp", "zap", "for_gateway");
	var sprotocol = document.outbound.protocol.value;
	var sgateway = document.outbound.gateway.value;
	var currentdiv;

	for(var i=0; i<protocols.length; i++)
	{
		currentdiv = document.getElementById(protocols[i]);
		if(currentdiv == null)
			continue;

		if(currentdiv.style.display == "block")
			currentdiv.style.display = "none"; 
	}	
	if(objname == "for_gateway") {
		document.outbound.protocol.selectedIndex = 0;
		currentdiv = document.getElementById("for_gateway");
	}else{
		document.outbound.gateway.selectedIndex = 0;
		currentdiv = document.getElementById(sprotocol);
	}
	if(currentdiv == null)
		return false;
	if(currentdiv.style.display != "block")
		currentdiv.style.display = "block";
}

function gateway_type()
{
	var divname, div;

/*	var elems = document.outbound.elements;
	for(i=0; i<elems.length;i++)
	{
		alert(elems[i].name);
		if (i>2)
			break;
	}
	return;*/

	var radio = document['forms']['outbound']['gateway_with_registration'];
	for(var i=0; i<radio.length; i++)
	{
		divname = radio[i].value;
		div = document.getElementById('div_'+divname);
		if (div == null)
			continue;
		if (radio[i].checked == true)
			div.style.display = "block";
		else
			div.style.display = "none";
	}

	var gwtype = "reg";
	var othergw = "noreg";
	var protocols = new Array("sip", "h323", "iax", "zap", "wp");

	for(i=0; i<protocols.length; i++) 
	{
		currentdiv = document.getElementById("div_"+gwtype+"_"+protocols[i]);
		if(currentdiv == null)
			continue;
		if(currentdiv.style.display == "block")
			currentdiv.style.display = "none";
	}
	for(i=0; i<protocols.length; i++) 
	{
		currentdiv = document.getElementById("div_"+othergw+"_"+protocols[i]);
		if(currentdiv == null)
			continue;
		if(currentdiv.style.display == "block")
			currentdiv.style.display = "none";
	}
}

function advanced(identifier)
{
	var elems = document.outbound.elements;
	var elem_name;
	var elem;

	for(var i=0;i<elems.length;i++)
	{
		elem_name = elems[i].name;
		if(elem_name.substr(0,identifier.length) != identifier)
			continue;
		var elem = document.getElementById("tr_"+elem_name); 
		if(elem == null)
			continue;
		if(elem.style.display == null)
			continue;
		if(elem.style.display == "none")
			elem.style.display = "table-row";
		else
			if(elem.style.display == "table-row")
				elem.style.display = "none";
	}

	var img = document.getElementById(identifier+"advanced");
	var imgsrc= img.src;
	var imgarray = imgsrc.split("/");
	if(imgarray[imgarray.length - 1] == "advanced.jpg"){
		imgarray[imgarray.length - 1] = "basic.jpg";
		img.title = "Hide advanced fields";
	}else{
		imgarray[imgarray.length - 1] = "advanced.jpg";
		img.title = "Show advanced fields";
	}

	img.src = imgarray.join("/");
}

function comute_destination(elem_type)
{
	var elem = document.getElementById(elem_type);
	var other_elem = (elem_type == "group") ? "number" : "group";
	var unselect_elem = document.getElementById(other_elem);

	if(other_elem == "number")
		unselect_elem.value = '';
	else
		unselect_elem.selectedIndex = 0;
}

function dependant_fields()
{
	var prot = document.getElementById("protocol");
	var sel_prot = prot.options[prot.selectedIndex].value;
	var field, textf;

	var fields = new Array("ip_address", "netmask", "gateway");
	for(var i=0; i<fields.length; i++)
	{
		field = document.getElementById("div_"+fields[i]);
		textf = document.getElementById("text_"+fields[i]);
		if(sel_prot == "static") {
			field.style.display = "table-cell";
			textf.display = "none";
		}else if(sel_prot == "dhcp" || sel_prot == "none") {
			field.style.display = "none";
			textf.display = "table-cell";
		}
	}
}

function change_background(color)
{
//alert(color);
this.bgColor =  "red";
/*	if(color == "gray")
	this.bgColor =  "#888888";
	else
	this.bgColor = "#eeeeee";*/
//alert("exit");
}

</script>

<script type="text/javascript" language="JavaScript1.2" src="jscript/togglebars.js"></script>