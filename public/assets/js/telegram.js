// SPDX-License-Identifier: GPL-2.0

var USER_MAP = {};
var USER_MAP_FIELDS = null;

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
	return escape_html(str).replace(/\n/g, "<br/>");
}


function apply_users(users)
{
	if (USER_MAP_FIELDS == null)
		USER_MAP_FIELDS = users.fields;

	for (let i in users.data)
		USER_MAP[i] = users.data[i];
}


function resolve_user(user_id, field = null)
{
	if (USER_MAP_FIELDS == null)
		return null;

	if (field == null)
		return USER_MAP[user_id];

	return USER_MAP[user_id][USER_MAP_FIELDS[field]];
}


function apply_messages(msgs)
{
	let	fields	= msgs.fields,
		data	= msgs.data,
		stub	= gid("msg-stub").innerHTML,
		chat_cg	= gid("chat-cg"),
		r	= "",
		i;


	let	f_user_id	= fields.user_id,
		f_text		= fields.text;

	for (i in data) {
		r += stub
			.replace("{{user_id}}", resolve_user(data[i][f_user_id], "username"))
			.replace("{{text}}", sanitize_text(data[i][f_text]));
	}
	chat_cg.innerHTML += r;
}


function load_message(json)
{
	if (json.status !== "ok")
		throw Error("Error: "+json.code+" "+json.msg);

	apply_users(json.msg.users);
	apply_messages(json.msg.messages);
}

fetch_msg(-1001483770714, 0, 300, function (json) {
	let chat_cg = gid("chat-cg");
	load_message(json);
	chat_cg.scrollTo(0, chat_cg.scrollHeight);
});
