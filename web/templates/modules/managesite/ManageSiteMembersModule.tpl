<h1>{t}Membership Policy{/t}</h1>

<p>
	{t}These settings will help you manage the way new users can become members of this Site.
	It of course depends on you if you need any members at all. But you can make it easier
	for them to join.{/t}
</p>

<p>
	{t}The settings apply to the modules <tt>[[module&nbsp;MembershipApply]]</tt> and
	<tt>[[module&nbsp;MembershipByPassword]]</tt> that are by default located at page
	<a href="/system:join" target="_blank"><tt>system:join</tt></a> (which you
	are encouraged to customize).{/t}
</p>
<p>
	{t}It depends on you what privileges/permissions your members will be granted. The permissions
	can be configured using the <em>Permissions</em> settings on the left panel.{/t}
</p>

<form id="sm-mem-form">
	<h2>{t}Applying for membership{/t}</h2>
	{t}
	<p>
		You can allow other users of {$SERVICE_NAME} to apply for membership by filling a form provided
		by the module
	</p>
		<div class="code"><pre>[[module MembershipApply]</pre></div>
	<p>
		(usually found at page <a href="/system:join" target="_blank"><tt>system:join</tt></a>,
		but you can put this piece of code anywhere)
	</p>
	{/t}
	<table class="form">
		<tr>
			<td>
				{t}Enable membership by applying{/t}:
			</td>
			<td>
				<input class="checkbox" type="checkbox" name="by_apply" {if $settings->getAllowMembershipByApply() == true}checked="checked"{/if}/>
			</td>
		</tr>
	</table>


	<h2>{t}Membership by password{/t}</h2>

	{t}
	<p>
		It is also possible to become a member by providing a valid password. The corresponding
		widget (form) is generated by the module
	</p>
		<div class="code"><pre>[[module MembershipByPassword]]</pre></div>
	<p>
		Just enter the password below and let your friends know it somehow. If you want anyone
		to apply, just publish your password on the same page where users are supposed to
		enter it.
	</p>
	{/t}
	<table class="form">
		<tr>
			<td>
				{t}Enable membership by password{/t}:
			</td>
			<td>
				<input class="checkbox" type="checkbox" name="by_password" {if $settings->getAllowMembershipByPassword() == true}checked="checked"{/if}/>
			</td>
		</tr>
		<tr>
			<td>
				{t}Password{/t}:
			</td>
			<td>
				<input class="text" type="text" name="password" size="30" value="{$settings->getMembershipPassword()|escape}"/>
			</td>
		</tr>
	</table>


	<div class="buttons">
		<input type="button" value="{t}cancel{/t}" id="sm-members-cancel"/>
		<input type="button" value="{t}save changes{/t}" id="sm-members-save"/>
	</div>
</form>

<h2>{t}Other ways of getting new members{/t}</h2>

<ul>
	<li><a href="javascript:;" onclick="Wikijump.modules.ManagerSiteModule.utils.loadModule('sm-members-invite')">{t}Invite existing Wikijump users{/t}</a></li>
	<li><b><a href="javascript:;" onclick="Wikijump.modules.ManagerSiteModule.utils.loadModule('sm-email-invitations')">{t}Invite new people by email{/t}</a></b></li>
</ul>
