<?php

use Mockery as m;

class MailerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMailerSendSendsMessageWithProperViewContent()
	{
		unset($_SERVER['__mailer.test']);
		$mailer = $this->getMock('Illuminate\Mail\Mailer', array('createMessage'), $this->getMocks());
		$message = m::mock('StdClass');
		$mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('foo', array('data', 'message' => $message))->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
		$message->shouldReceive('setFrom')->never();
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message);
		$mailer->send('foo', array('data'), function($m) { $_SERVER['__mailer.test'] = $m; });
		unset($_SERVER['__mailer.test']);
	}


	public function testMailerSendSendsMessageWithProperPlainViewContent()
	{
		unset($_SERVER['__mailer.test']);
		$mailer = $this->getMock('Illuminate\Mail\Mailer', array('createMessage'), $this->getMocks());
		$message = m::mock('StdClass');
		$mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('foo', array('data', 'message' => $message))->andReturn($view);
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('bar', array('data', 'message' => $message))->andReturn($view);
		$view->shouldReceive('render')->twice()->andReturn('rendered.view');
		$message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
		$message->shouldReceive('addPart')->once()->with('rendered.view', 'text/plain');
		$message->shouldReceive('setFrom')->never();
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message);
		$mailer->send(array('foo', 'bar'), array('data'), function($m) { $_SERVER['__mailer.test'] = $m; });
		unset($_SERVER['__mailer.test']);
	}


	public function testMessagesCanBeLoggedInsteadOfSent()
	{
		$mailer = $this->getMock('Illuminate\Mail\Mailer', array('createMessage'), $this->getMocks());
		$message = m::mock('StdClass');
		$mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('foo', array('data', 'message' => $message))->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
		$message->shouldReceive('setFrom')->never();
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$message->shouldReceive('getTo')->once()->andReturn(array('taylor@userscape.com' => 'Taylor'));
		$message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
		$mailer->getSwiftMailer()->shouldReceive('send')->never();
		$logger = m::mock('Illuminate\Log\Writer');
		$logger->shouldReceive('info')->once()->with('Pretending to mail message to: taylor@userscape.com');
		$mailer->setLogger($logger);
		$mailer->pretend();

		$mailer->send('foo', array('data'), function($m) {});
	}


	public function testMailerCanResolveMailerClasses()
	{
		$mailer = $this->getMock('Illuminate\Mail\Mailer', array('createMessage'), $this->getMocks());
		$message = m::mock('StdClass');
		$mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
		$view = m::mock('StdClass');
		$container = new Illuminate\Container\Container;
		$mailer->setContainer($container);
		$mockMailer = m::mock('StdClass');
		$container['FooMailer'] = $container->share(function() use ($mockMailer)
		{
			return $mockMailer;
		});
		$mockMailer->shouldReceive('mail')->once()->with($message);
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('foo', array('data', 'message' => $message))->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
		$message->shouldReceive('setFrom')->never();
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message);
		$mailer->send('foo', array('data'), 'FooMailer');
	}


	public function testGlobalFromIsRespectedOnAllMessages()
	{
		unset($_SERVER['__mailer.test']);
		$mailer = $this->getMailer();
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$mailer->alwaysFrom('taylorotwell@gmail.com', 'Taylor Otwell');
		$me = $this;
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type('Swift_Message'))->andReturnUsing(function($message) use ($me)
		{
			$me->assertEquals(array('taylorotwell@gmail.com' => 'Taylor Otwell'), $message->getFrom());
		});
		$mailer->send('foo', array('data'), function($m) {});
	}


	protected function getMailer()
	{
		return new Illuminate\Mail\Mailer(m::mock('Illuminate\View\Environment'), m::mock('Swift_Mailer'));
	}


	protected function getMocks()
	{
		return array(m::mock('Illuminate\View\Environment'), m::mock('Swift_Mailer'));
	}

}