import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  hasMany,
  HasMany,
} from '@ioc:Adonis/Lucid/Orm'
import Email from './Email'
import UnreadEmailActivity from './UnreadEmailActivity'
import User from './User'
import { isRelationshipPreloaded } from 'App/Helpers/model'

export default class UnreadEmail extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public emailId: string

  @column()
  public userId: string

  @column()
  public device: string

  @column()
  public isDeleted: boolean = false

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Email)
  public email: BelongsTo<typeof Email>

  @hasMany(() => UnreadEmailActivity)
  public activities: HasMany<typeof UnreadEmailActivity>

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @computed()
  public get date() {
    return this.createdAt.toLocaleString(DateTime.DATE_MED)
  }

  @computed()
  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedUnreadEmailInfo() {
    isRelationshipPreloaded(this, 'email')
    isRelationshipPreloaded(this, 'activities')

    const { id, email, emailId, device, date, time, activities } = this

    return {
      id,
      emailId,
      device,
      recipient: email.recipient,
      subject: email.subject,
      gmailMessageId: email.gmailMessageId,
      gmailThreadId: email.gmailThreadId,
      date,
      time,
      activities: activities.map((activity) => activity.serializedUnreadEmailActivity),
    }
  }
}
