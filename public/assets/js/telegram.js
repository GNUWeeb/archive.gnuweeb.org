// SPDX-License-Identifier: GPL-2.0


function gid(id) {
	return document.getElementById(id);
}


function fetch_msg(group_id, start_at, limit, callback)
{
	let ch = new XMLHttpRequest;
	ch.onload = function () {
		try {
			if (callback)
				callback(JSON.parse(this.responseText));
		} catch (e) {
			alert("Error: " + e.message);
			throw e;
		}
	};
	ch.open("GET", "/telegram/api.php?group_id="+group_id+"&start_at"+
		start_at+"&limit="+limit);
	ch.send();
}


function escape_html(str) {
	return str
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function sanitize_text(str)
{
	return escape_html(str)
		.replace(/^\t/g, "&nbsp;")
		.replace(/\n/g, "<br/>");
}


function load_message(json)
{
	let stub = gid("msg-stub").innerHTML, chat_cg = gid("chat-cg"),
		fields, data, i, r = "";

	if (json.status !== "ok")
		throw Error("Error: "+json.code+" "+json.msg);

	fields = json.msg.fields;
	data   = json.msg.data;

	let f_text = fields.text;
	let f_user_id = fields.user_id;

	for (i in data) {
		r += stub
			.replace("{{user_id}}", "uid:"+data[i][f_user_id])
			.replace("{{text}}", sanitize_text(data[i][f_text]));
	}
	chat_cg.innerHTML += r;
}

fetch_msg(-1001483770714, 0, 300, function (json) {
	let chat_cg = gid("chat-cg");
	load_message(json);
	chat_cg.scrollTo(0, chat_cg.scrollHeight);
});
