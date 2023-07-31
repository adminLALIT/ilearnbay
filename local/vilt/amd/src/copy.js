function copyAddress() {
    var temp = $("<input>");
    $("body").append(temp);
    temp.val($('#copylink').attr('data')).select();
    document.execCommand("copy");
    temp.remove();
    alert("Link Address Copied!");
}