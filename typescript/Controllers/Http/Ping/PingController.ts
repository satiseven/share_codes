import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Ping from 'App/Models/Ping'
import User from 'App/Models/User'
import GetPingByIdValidator from 'App/Validators/Ping/GetPingByIdValidator'

export default class PingController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const pings = await Ping.query().where({ userId: user.id })

    return {
      data: {
        pings: pings.map((ping) => ping.serializedPingInfo),
      },
    }
  }

  public async show({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingByIdValidator)
    const ping = await Ping.query().where({ id: params.id, userId: user.id }).firstOrFail()

    return {
      data: {
        ping: ping.serializedPingInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { name, emailId } = request.body()
    const ping = await Ping.create({ name, emailId, userId: user.id })

    return {
      data: {
        message: 'Ping created successfully',
        ping: ping.serializedPingInfo,
      },
    }
  }

  public async destroy({ request }: HttpContextContract) {
    const { params } = await request.validate(GetPingByIdValidator)
    const ping = await Ping.findByOrFail('id', params.id)

    await ping.delete()

    return {
      data: {
        message: 'Ping deleted successfully',
      },
    }
  }
}
